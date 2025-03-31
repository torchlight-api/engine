<?php

namespace Torchlight\Engine;

use InvalidArgumentException;
use Phiki\Environment\Environment;
use Phiki\Grammar\Grammar;
use Phiki\Grammar\GrammarRepository;
use Phiki\Phiki as BasePhiki;
use Phiki\Theme\Theme;
use Phiki\Token\Token;
use Phiki\Tokenizer;
use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\AutoLinkAnnotation;
use Torchlight\Engine\Annotations\CollapseAnnotation;
use Torchlight\Engine\Annotations\Diff\DiffAddAnnotation;
use Torchlight\Engine\Annotations\Diff\DiffRemoveAnnotation;
use Torchlight\Engine\Annotations\FocusAnnotation;
use Torchlight\Engine\Annotations\HighlightAnnotation;
use Torchlight\Engine\Annotations\MonoAnnotation;
use Torchlight\Engine\Annotations\Parser\AnnotationTokenParser;
use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Processor;
use Torchlight\Engine\Annotations\Ranges\AnnotationRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Annotations\ReindexAnnotation;
use Torchlight\Engine\Concerns\LoadsGrammars;
use Torchlight\Engine\Concerns\ManagesCommentTokens;
use Torchlight\Engine\Concerns\ManagesPreprocessors;
use Torchlight\Engine\Concerns\ManagesThemes;
use Torchlight\Engine\Concerns\MergesTokens;
use Torchlight\Engine\Concerns\ProcessesLanguages;
use Torchlight\Engine\Concerns\ProcessesTextJson;
use Torchlight\Engine\Exceptions\InvalidJsonException;
use Torchlight\Engine\Generators\HtmlGenerator;
use Torchlight\Engine\Generators\RenderedBlock;
use Torchlight\Engine\Support\Str;
use Torchlight\Engine\Theme\Highlighting\Highlighter;
use Torchlight\Engine\Theme\ThemeRepository;

class Engine extends BasePhiki
{
    public const VERSION = '0.1.0';

    use LoadsGrammars,
        ManagesCommentTokens,
        ManagesPreprocessors,
        ManagesThemes,
        MergesTokens,
        ProcessesLanguages,
        ProcessesTextJson;

    protected array $plainTextScopes = [
        'text.txt',
        'text.bibtex',
        'text.csv',
        'text.tsv',
    ];

    protected string $activeScopeName = '';

    protected AnnotationTokenParser $annotationParser;

    /**
     * @var \Torchlight\Engine\Annotations\Parser\ParsedAnnotation[]
     */
    protected array $parsedAnnotations = [];

    protected Processor $annotationEngine;

    protected Options $torchlightOptions;

    protected int $sourceLineOffset = 0;

    protected bool $annotationsEnabled = true;

    protected string $cleanedText = '';

    protected ?array $overrideThemes = null;

    public function __construct(?Environment $environment = null)
    {
        if ($environment === null) {
            $environment = new Environment;
            $environment->disableStrictMode()
                ->useGrammarRepository(new GrammarRepository)
                ->useThemeRepository(new ThemeRepository);
        }

        parent::__construct($environment);

        $this->torchlightOptions = Options::default();

        $this->annotationEngine = new Processor;

        $this->annotationParser = new AnnotationTokenParser;

        $this
            ->loadThemes()
            ->loadGrammars()
            ->addDefaultAnnotations();
    }

    protected function addDefaultAnnotations(): static
    {
        return $this->addAnnotations([
            ReindexAnnotation::class,
            AutoLinkAnnotation::class,
            FocusAnnotation::class,
            HighlightAnnotation::class,
            DiffAddAnnotation::class,
            DiffRemoveAnnotation::class,
            CollapseAnnotation::class,
            MonoAnnotation::class,
        ]);
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function addAnnotations(array $classNames): static
    {
        foreach ($classNames as $className) {
            $this->addAnnotation($className);
        }

        return $this;
    }

    public function addAnnotation(string $className): static
    {
        if (! class_exists($className) || ! is_subclass_of($className, AbstractAnnotation::class)) {
            throw new InvalidArgumentException("Provided [{$className}] is not a valid annotation class.");
        }

        /** @var AbstractAnnotation $instance */
        $instance = new $className($this->annotationEngine);

        $this->annotationEngine->addAnnotation($instance::$name, $instance);
        $this->annotationParser->addAnnotationName($instance::$name);

        foreach ($instance::$aliases as $alias) {
            $this->annotationEngine->addAnnotation($alias, $instance);
            $this->annotationParser->addAnnotationName($alias);
        }

        return $this;
    }

    protected function reset(): void
    {
        $this->overrideThemes = null;
        $this->annotationsEnabled = true;
        $this->sourceLineOffset = 0;
        $this->annotationEngine->reset();
        $this->annotationParser->reset();
        $this->parsedAnnotations = [];
    }

    protected function makeGenerator(?string $grammarName, array $themes, bool $withGutter = false): HtmlGenerator
    {
        $generator = new HtmlGenerator(
            $grammarName,
            $themes,
            $withGutter
        );

        $generator->setHighlighter($this->makeHighlighter($themes));

        $options = $this->annotationEngine->getGenerationOptions();

        foreach ($options->gutters as $gutter) {
            $gutter->setHtmlGenerator($generator);
        }

        foreach ($this->annotationEngine->getAnnotations() as $annotation) {
            $annotation->setHtmlGenerator($generator);
        }

        $generator
            ->setGenerationOptions($options)
            ->setCleanedText($this->cleanedText);

        return $generator;
    }

    protected function isPlainText(): bool
    {
        return in_array($this->activeScopeName, $this->plainTextScopes);
    }

    protected function isJson(): bool
    {
        return $this->activeScopeName === 'source.json';
    }

    protected function beginsWithAnnotation(Token $token): bool
    {
        return str_starts_with(trim($token->text), '[tl! ');
    }

    protected function containsTorchlightAnnotation(Token $token): bool
    {
        preg_match_all(AnnotationTokenParser::ANNOTATION_PATTERN, $token->text, $matches);

        if (empty($matches) || empty($matches[0])) {
            return false;
        }

        if ($this->isPlainText() || $this->isJson()) {
            return true;
        }

        if ($this->isComment($token)) {
            return true;
        }

        return false;
    }

    protected function removeAnnotationFromToken(Token $token): array
    {
        $originalText = $token->text;
        $token->text = preg_replace(AnnotationTokenParser::ANNOTATION_PATTERN, '', $token->text);
        $token->text = $this->cleanCommentText($token->text, $this->activeScopeName);

        return [$originalText, $token];
    }

    protected function parseAnnotationsInText(string $text, int $line): void
    {
        foreach ($this->annotationParser->parseText($text, $line - $this->sourceLineOffset)->annotations as $annotation) {
            $this->parsedAnnotations[] = $annotation;
        }
    }

    protected function parseBlockOptions(string $text): void
    {
        $this->sourceLineOffset = 1;
        $text = Str::after($text, '{');
        $text = Str::beforeLast($text, '}');

        $jsonResult = json_decode('{'.$text.'}', true);

        if ($jsonResult === null && strlen(trim($text)) > 0) {
            $jsonError = json_last_error_msg();
            throw new InvalidJsonException("{$jsonError} when parsing options [{$text}].");
        }

        $this->torchlightOptions = Options::fromArray(array_merge($this->torchlightOptions->toArray(), $jsonResult));

        if (count($this->torchlightOptions->themes) > 0) {
            $this->overrideThemes = $this->torchlightOptions->themes;

            // TODO: Review.
            if (count($this->overrideThemes) === 1 && ! is_string(array_keys($this->overrideThemes)[0])) {
                $this->overrideThemes = [
                    'light' => $this->overrideThemes[0],
                ];
            }
        }

        $this->annotationsEnabled = $this->torchlightOptions->annotationsEnabled;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->torchlightOptions = $options;

        return $this;
    }

    public function getTorchlightOptions(): Options
    {
        return $this->torchlightOptions;
    }

    protected function extractText(array $lines): string
    {
        $text = '';

        foreach ($lines as $tokens) {
            $line = '';

            /** @var Token $token */
            foreach ($tokens as $token) {
                $line .= $token->text;
            }

            $text .= "\n".rtrim($line);
        }

        return $text;
    }

    protected function parseAnnotationTokens(array $tokens): array
    {
        if ($this->isJson() || $this->isPlainText()) {
            $processedTokens = $this->processTextOrJson($tokens);
        } else {
            $processedTokens = $this->processLanguage($tokens);
        }

        $this->cleanedText = $this->extractText($processedTokens);

        return $processedTokens;
    }

    public function getTokens(string $code, string|Grammar $grammar): array
    {
        $languageName = is_string($grammar) ? $grammar : null;

        $grammar = $this->environment->resolveGrammar($grammar);

        if (! $languageName) {
            $languageName = $grammar->name;
        }

        if (property_exists($grammar, 'scopeName')) {
            $this->activeScopeName = $grammar->scopeName;
        } else {
            $this->activeScopeName = 'text.txt';
        }

        $tokenizer = new Tokenizer($grammar, $this->environment);

        return $tokenizer->tokenize($code);
    }

    public function codeToTokens(string $code, string|Grammar $grammar): array
    {
        if (is_string($grammar) && ! $grammar) {
            $grammar = 'text';
        }

        $tokens = $this->getTokens($code, $grammar);

        $languageName = is_string($grammar) ? $grammar : null;
        $grammar = $this->environment->resolveGrammar($grammar);

        if (! $languageName) {
            $languageName = $grammar->name;
        }

        $tokens = $this->mergeTokens($tokens);

        $tokens = $this->preprocess($tokens, $code, $grammar, $languageName);

        return $this->parseAnnotationTokens($tokens);
    }

    private function makeHighlighter($themes): Highlighter
    {
        return new Highlighter($themes);
    }

    protected function createAnnotationsFromOptions(string $annotationName, array $ranges): static
    {
        foreach ($ranges as $range) {
            [$start, $end] = $range;

            $annotation = new ParsedAnnotation;
            $annotation->index = count($this->parsedAnnotations);
            $annotation->sourceLine = $start;
            $annotation->name = $annotationName;
            $annotation->type = AnnotationType::Named;

            $annotationRange = new AnnotationRange;
            $annotationRange->type = RangeType::Relative;
            $annotationRange->start = null;
            $annotationRange->end = ($end - $start);

            $annotation->range = $annotationRange;

            $this->parsedAnnotations[] = $annotation;
        }

        return $this;
    }

    private function createAllAnnotationsFromOptions(): void
    {
        $this
            ->createAnnotationsFromOptions('highlight', $this->torchlightOptions->highlightLines)
            ->createAnnotationsFromOptions('add', $this->torchlightOptions->addLines)
            ->createAnnotationsFromOptions('remove', $this->torchlightOptions->removeLines)
            ->createAnnotationsFromOptions('focus', $this->torchlightOptions->focusLines)
            ->createAnnotationsFromOptions('autolink', $this->torchlightOptions->autolinkLines)
            ->createAnnotationsFromOptions('mono', $this->torchlightOptions->monoLines);
    }

    private function getHtmlGeneratorForCode(string $code, Grammar|string $grammar, Theme|array|string $theme, bool $withGutter = false, bool $withWrapper = false): array
    {
        if ($grammar === 'php') {
            if (str_contains($code, '<?php') || str_contains($code, '<?=')) {
                $grammar = 'php-html';
            }
        }

        $this->torchlightOptions = Options::default();

        if ((is_string($grammar) && mb_strlen($grammar) > 0) && ! $this->environment->getGrammarRepository()->has($grammar) && $this->torchlightOptions->fallbackOnUnknownGrammar) {
            $grammar = 'plaintext';
        }

        if (is_string($theme) && str_contains($theme, ':')) {
            $theme = Options::adjustOptionThemes([$theme]);
        }

        // Remove trailing whitespace.
        $code = rtrim($code);

        $this->reset();

        // We need to tokenize the code first as this will
        // retrieve the annotation information which we
        // need to have for the remaining processes
        $tokens = $this->codeToTokens($code, $grammar);
        $themes = $this->wrapThemes($this->overrideThemes ?? $theme);

        $generator = $this->makeGenerator(
            match (true) {
                is_string($grammar) => $grammar,
                default => $this->environment->resolveGrammar($grammar)->name,
            },
            $this->wrapThemes($this->overrideThemes ?? $theme),
            $withGutter
        );

        $this->createAllAnnotationsFromOptions();
        $highlighter = $this->makeHighlighter($themes);

        $tokens = $this->annotationEngine
            ->setHighlighter($highlighter)
            ->setTorchlightOptions($this->torchlightOptions)
            ->process(
                $this->parsedAnnotations,
                $tokens,
            );

        $generator
            ->setTorchlightOptions($this->torchlightOptions);

        return [$generator, $highlighter->highlight($tokens)];
    }

    public function renderCode(string $code, Grammar|string $grammar, Theme|array|string $theme): RenderedBlock
    {
        [$generator, $highlightedTokens] = $this->getHtmlGeneratorForCode($code, $grammar, $theme, true, false);

        return $generator->renderBlock($highlightedTokens);
    }

    public function codeToHtml(string $code, Grammar|string $grammar, Theme|array|string $theme, bool $withGutter = false, bool $withWrapper = false): string
    {
        [$generator, $highlightedTokens] = $this->getHtmlGeneratorForCode($code, $grammar, $theme, $withGutter, $withWrapper);

        return $generator->generate($highlightedTokens);
    }

    /**
     * @return ParsedAnnotation[]
     */
    public function getParsedAnnotations(): array
    {
        return $this->parsedAnnotations;
    }
}
