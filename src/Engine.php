<?php

namespace Torchlight\Engine;

use InvalidArgumentException;
use Phiki\Environment;
use Phiki\Grammar\Grammar;
use Phiki\Grammar\ParsedGrammar;
use Phiki\Highlighting\Highlighter;
use Phiki\TextMate\Tokenizer;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\Theme as PhikiTheme;
use Phiki\Token\HighlightedToken;
use Phiki\Token\Token;
use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\AnnotationEngine;
use Torchlight\Engine\Annotations\Attributes\CssClassAnnotation;
use Torchlight\Engine\Annotations\Attributes\IdAnnotation;
use Torchlight\Engine\Annotations\AutoLinkAnnotation;
use Torchlight\Engine\Annotations\CodeLensAnnotation;
use Torchlight\Engine\Annotations\CollapseAnnotation;
use Torchlight\Engine\Annotations\Diff\DiffAddAnnotation;
use Torchlight\Engine\Annotations\Diff\DiffRemoveAnnotation;
use Torchlight\Engine\Annotations\Diff\WordDiffAnnotation;
use Torchlight\Engine\Annotations\FocusAnnotation;
use Torchlight\Engine\Annotations\GutterContentAnnotation;
use Torchlight\Engine\Annotations\HideAnnotation;
use Torchlight\Engine\Annotations\HighlightAnnotation;
use Torchlight\Engine\Annotations\LinkAnnotation;
use Torchlight\Engine\Annotations\MacroAnnotation;
use Torchlight\Engine\Annotations\MarkAnnotation;
use Torchlight\Engine\Annotations\MonoAnnotation;
use Torchlight\Engine\Annotations\Parser\AnnotationTokenParser;
use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Ranges\AnnotationRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Annotations\RegionAnnotation;
use Torchlight\Engine\Annotations\ReindexAnnotation;
use Torchlight\Engine\Concerns\ManagesCommentTokens;
use Torchlight\Engine\Concerns\ManagesPreprocessors;
use Torchlight\Engine\Concerns\ManagesReplacers;
use Torchlight\Engine\Concerns\ManagesThemes;
use Torchlight\Engine\Concerns\ProcessesLanguages;
use Torchlight\Engine\Concerns\ProcessesTextJson;
use Torchlight\Engine\Contracts\BlockDecorator;
use Torchlight\Engine\Contracts\TokenTransformer;
use Torchlight\Engine\Exceptions\InvalidJsonException;
use Torchlight\Engine\Generators\BlockDecorators\CopyTargetDecorator;
use Torchlight\Engine\Generators\GeneratorFactory;
use Torchlight\Engine\Generators\HtmlGenerator;
use Torchlight\Engine\Generators\RenderedBlock;
use Torchlight\Engine\Generators\TokenTransformers\FileTreeTransformer;
use Torchlight\Engine\Generators\TokenTransformers\IndentGuideTransformer;
use Torchlight\Engine\Pipeline\ProcessedTokens;
use Torchlight\Engine\Pipeline\RenderState;
use Torchlight\Engine\Support\Str;
use Torchlight\Engine\Support\TokenMerger;
use Torchlight\Engine\Theme\Theme;

class Engine
{
    public const VERSION = '0.1.0';

    use ManagesCommentTokens,
        ManagesPreprocessors,
        ManagesReplacers,
        ManagesThemes,
        ProcessesLanguages,
        ProcessesTextJson;

    /**
     * @var array<string, string>
     */
    protected array $extraGrammars = [
        'alpine' => __DIR__.'/../resources/languages/alpine.tmLanguage.json',
        'curl' => __DIR__.'/../resources/languages/curl.tmLanguage.json',
        'env' => __DIR__.'/../resources/languages/env.tmLanguage.json',
        'files' => __DIR__.'/../resources/languages/files.tmLanguage.json',
        'git-ignore' => __DIR__.'/../resources/languages/ignore.tmLanguage.json',
        'mysql-explain' => __DIR__.'/../resources/languages/mysql-explain.tmLanguage.json',
        'php-html' => __DIR__.'/../resources/languages/php-html.tmLanguage.json',
        'shell' => __DIR__.'/../resources/languages/shell.tmLanguage.json',
        'makefile' => __DIR__.'/../resources/languages/make.tmLanguage.json',
        'jinja-html' => __DIR__.'/../vendor/phiki/phiki/resources/grammars/jinja-html.json',
    ];

    /**
     * Grammar aliases for common alternative names.
     *
     * @var array<string, string>
     */
    public static array $grammarAliases = [
        'alpinejs' => 'alpine',
        'shellscript' => 'shell',
        'gitignore' => 'git-ignore',
        'pls' => 'plsql',
        'html-ruby-erb' => 'erb',
        'actionscript' => 'actionscript-3',
        'dockerfile' => 'docker',
        'make' => 'makefile',
    ];

    /** @var list<string> */
    protected array $plainTextScopes = [
        'text.txt',
        'text.plain',
        'text.bibtex',
        'text.csv',
        'text.tsv',
    ];

    /** @var array<string, string> */
    protected array $commonVanityLabels = [
        'php-html' => 'php',
    ];

    protected AnnotationTokenParser $annotationParser;

    protected AnnotationEngine $annotationEngine;

    protected Options $torchlightOptions;

    protected ?Options $userBaseOptions = null;

    protected RenderState $state;

    protected string $blockOptionsKeyword = 'torchlight! ';

    /**
     * @var array<string, list<callable(string, string): ?string>>
     */
    protected array $grammarTransformers = [];

    /**
     * @var list<callable(): TokenTransformer>
     */
    protected array $tokenTransformerFactories = [];

    /**
     * @var list<callable(): BlockDecorator>
     */
    protected array $blockDecoratorFactories = [];

    protected GeneratorFactory $generatorFactory;

    public function __construct(protected Environment $environment = new Environment)
    {
        $this->torchlightOptions = Options::default();
        $this->state = new RenderState;

        $this->annotationEngine = new AnnotationEngine;

        $this->annotationParser = new AnnotationTokenParser;

        $this->generatorFactory = new GeneratorFactory;

        $this->annotationEngine->getRegistry()
            ->registerAnnotation(new CssClassAnnotation($this->annotationEngine))
            ->registerAnnotation(new IdAnnotation($this->annotationEngine));

        $this->syncAnnotationParser();

        $this->registerGrammarTransformer('php', function (string $code): ?string {
            if (str_contains($code, '<?php') || str_contains($code, '<?=')) {
                return 'php-html';
            }

            return null;
        });

        $this
            ->registerTokenTransformerFactory(fn () => new FileTreeTransformer)
            ->registerTokenTransformerFactory(fn () => new IndentGuideTransformer)
            ->registerBlockDecoratorFactory(fn () => new CopyTargetDecorator)
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
            WordDiffAnnotation::class,
            RegionAnnotation::class,
            GutterContentAnnotation::class,
            MarkAnnotation::class,
            HideAnnotation::class,
            LinkAnnotation::class,
            CodeLensAnnotation::class,
        ]);
    }

    protected function loadGrammars(): static
    {
        foreach ($this->extraGrammars as $grammar => $file) {
            $this->environment->grammars->register($grammar, $file);
        }

        foreach (self::$grammarAliases as $alias => $target) {
            $this->environment->grammars->alias($alias, $target);
        }

        return $this;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * @param  list<class-string<AbstractAnnotation>>  $classNames
     */
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

        $this->annotationEngine->getRegistry()->registerAnnotation($instance);

        $this->syncAnnotationParser();

        return $this;
    }

    /**
     * @param  list<string>  $components
     */
    public function registerAnnotationMacro(string $name, array $components): static
    {
        $macro = new MacroAnnotation($this->annotationEngine, $name, $components);

        $this->annotationEngine->getRegistry()->register($name, $macro);

        $this->syncAnnotationParser();

        return $this;
    }

    public function registerAnnotation(string $name, \Closure $callback, bool $charRanges = false): static
    {
        $annotation = new Annotations\ClosureAnnotation(
            $this->annotationEngine,
            $name,
            $callback,
            $charRanges,
        );

        $this->annotationEngine->getRegistry()->register($name, $annotation);

        $this->syncAnnotationParser();

        return $this;
    }

    public function removeAnnotation(string $name): static
    {
        $this->annotationEngine->getRegistry()->unregister($name);

        $this->syncAnnotationParser();

        return $this;
    }

    protected function beginNewRender(): void
    {
        $this->state = new RenderState;
        $this->annotationEngine->reset();
        $this->annotationParser->reset();
    }

    private function syncAnnotationParser(): void
    {
        $registry = $this->annotationEngine->getRegistry();
        $this->annotationParser
            ->setAnnotationNames(array_values($registry->getRegisteredNames()))
            ->setRegisteredPrefixes(array_values($registry->getRegisteredPrefixes()));
    }

    /**
     * @param  array<string, ParsedTheme>  $themes
     */
    protected function makeGenerator(?string $grammarName, array $themes, Highlighter $highlighter): HtmlGenerator
    {
        return $this->generatorFactory
            ->setTokenTransformerFactories($this->tokenTransformerFactories)
            ->setBlockDecoratorFactories($this->blockDecoratorFactories)
            ->create(
                $grammarName,
                $themes,
                $highlighter,
                $this->annotationEngine,
                $this->torchlightOptions,
                $this->state->cleanedText,
                $this->state->languageVanityLabel,
            );
    }

    protected function prepareGrammar(string $code, Grammar|string $grammar): PreparedGrammar
    {
        $vanityLabel = '';

        if (is_string($grammar) && isset($this->grammarTransformers[$grammar])) {
            foreach ($this->grammarTransformers[$grammar] as $transformer) {
                $result = $transformer($code, $grammar);
                if ($result !== null) {
                    $grammar = $result;
                    break;
                }
            }
        }

        if ((is_string($grammar) && mb_strlen($grammar) > 0) && ! $this->environment->grammars->has($grammar) && $this->torchlightOptions->fallbackOnUnknownGrammar) {
            $vanityLabel = $grammar;
            $grammar = 'plaintext';
        }

        if (is_string($grammar) && array_key_exists($grammar, $this->commonVanityLabels)) {
            $vanityLabel = $this->commonVanityLabels[$grammar];
        }

        return new PreparedGrammar($grammar, $vanityLabel);
    }

    protected function isPlainText(): bool
    {
        return in_array($this->state->activeScopeName, $this->plainTextScopes);
    }

    protected function isJson(): bool
    {
        return $this->state->activeScopeName === 'source.json';
    }

    protected function beginsWithAnnotation(Token $token): bool
    {
        return str_starts_with(trim($token->text), '[tl! ');
    }

    protected function containsTorchlightAnnotation(Token $token): bool
    {
        preg_match_all(AnnotationTokenParser::ANNOTATION_PATTERN, $token->text, $matches);

        if (empty($matches[0])) {
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

    /**
     * @return array{0:string, 1:Token}
     */
    protected function removeAnnotationFromToken(Token $token): array
    {
        $originalText = $token->text;
        $token->text = preg_replace(AnnotationTokenParser::ANNOTATION_PATTERN, '', $token->text) ?? $token->text;
        $token->text = $this->cleanCommentText($token->text, $this->state->activeScopeName);

        return [$originalText, $token];
    }

    protected function parseAnnotationsInText(string $text, int $line): void
    {
        foreach ($this->annotationParser->parseText($text, $line - $this->state->sourceLineOffset)->annotations as $annotation) {
            $this->state->parsedAnnotations[] = $annotation;
        }
    }

    protected function parseBlockOptions(string $text): void
    {
        $this->state->sourceLineOffset = 1;
        $text = Str::after($text, '{');
        $text = Str::beforeLast($text, '}');

        $jsonResult = json_decode('{'.$text.'}', true);

        if ($jsonResult === null && strlen(trim($text)) > 0) {
            $jsonError = json_last_error_msg();
            throw new InvalidJsonException("{$jsonError} when parsing options [{$text}].");
        }

        /** @var array<string, mixed> $blockOptions */
        $blockOptions = is_array($jsonResult) ? $jsonResult : [];
        $this->torchlightOptions = $this->torchlightOptions->mergeWith($blockOptions);

        if (count($this->torchlightOptions->themes) > 0) {
            $this->state->overrideThemes = $this->torchlightOptions->themes;
        }

        $this->state->annotationsEnabled = $this->torchlightOptions->annotationsEnabled;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->torchlightOptions = $options;
        $this->userBaseOptions = $options;

        return $this;
    }

    public function getTorchlightOptions(): Options
    {
        return $this->torchlightOptions;
    }

    public function registerVanityLabel(string $grammarName, string $displayLabel): static
    {
        $this->commonVanityLabels[$grammarName] = $displayLabel;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getVanityLabels(): array
    {
        return $this->commonVanityLabels;
    }

    public function registerPlainTextScope(string $scope): static
    {
        if (! in_array($scope, $this->plainTextScopes)) {
            $this->plainTextScopes[] = $scope;
        }

        return $this;
    }

    public function unregisterPlainTextScope(string $scope): static
    {
        $this->plainTextScopes = array_values(array_filter(
            $this->plainTextScopes,
            fn (string $s): bool => $s !== $scope
        ));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPlainTextScopes(): array
    {
        return $this->plainTextScopes;
    }

    public function setBlockOptionsKeyword(string $keyword): static
    {
        $this->blockOptionsKeyword = $keyword;

        return $this;
    }

    public function getBlockOptionsKeyword(): string
    {
        return $this->blockOptionsKeyword;
    }

    public function addGutter(string $name, Generators\Gutters\AbstractGutter $gutter): static
    {
        $this->annotationEngine->addGutter($name, $gutter);

        return $this;
    }

    public function removeGutter(string $name): static
    {
        $this->annotationEngine->removeGutter($name);

        return $this;
    }

    public function hasGutter(string $name): bool
    {
        return $this->annotationEngine->hasGutter($name);
    }

    /**
     * @return Generators\Gutters\AbstractGutter[]
     */
    public function getGutters(): array
    {
        return $this->annotationEngine->getGutters();
    }

    public function setGutterPriority(string $name, int $priority): static
    {
        $this->annotationEngine->setGutterPriority($name, $priority);

        return $this;
    }

    public function placeGutterAfter(string $gutter, string $afterGutter): static
    {
        $this->annotationEngine->placeGutterAfter($gutter, $afterGutter);

        return $this;
    }

    public function placeGutterBefore(string $gutter, string $beforeGutter): static
    {
        $this->annotationEngine->placeGutterBefore($gutter, $beforeGutter);

        return $this;
    }

    public function getAnnotationEngine(): AnnotationEngine
    {
        return $this->annotationEngine;
    }

    /** @param callable(string, string): ?string $transformer */
    public function registerGrammarTransformer(string $grammar, callable $transformer): static
    {
        if (! isset($this->grammarTransformers[$grammar])) {
            $this->grammarTransformers[$grammar] = [];
        }

        $this->grammarTransformers[$grammar][] = $transformer;

        return $this;
    }

    public function removeGrammarTransformer(string $grammar, int $index): static
    {
        if (isset($this->grammarTransformers[$grammar][$index])) {
            array_splice($this->grammarTransformers[$grammar], $index, 1);

            // Clean up empty arrays
            if (empty($this->grammarTransformers[$grammar])) {
                unset($this->grammarTransformers[$grammar]);
            }
        }

        return $this;
    }

    public function removeGrammarTransformers(string $grammar): static
    {
        unset($this->grammarTransformers[$grammar]);

        return $this;
    }

    /**
     * @return array<string, list<callable(string, string): ?string>>
     */
    public function getGrammarTransformers(): array
    {
        return $this->grammarTransformers;
    }

    /** @param callable(): TokenTransformer $factory */
    public function registerTokenTransformerFactory(callable $factory): static
    {
        $this->tokenTransformerFactories[] = $factory;

        return $this;
    }

    public function registerTokenTransformer(TokenTransformer $transformer): static
    {
        return $this->registerTokenTransformerFactory(fn () => $transformer);
    }

    public function removeTokenTransformerFactory(int $index): static
    {
        if (isset($this->tokenTransformerFactories[$index])) {
            array_splice($this->tokenTransformerFactories, $index, 1);
        }

        return $this;
    }

    public function clearTokenTransformerFactories(): static
    {
        $this->tokenTransformerFactories = [];

        return $this;
    }

    /**
     * @return list<callable(): TokenTransformer>
     */
    public function getTokenTransformerFactories(): array
    {
        return $this->tokenTransformerFactories;
    }

    /** @param callable(): BlockDecorator $factory */
    public function registerBlockDecoratorFactory(callable $factory): static
    {
        $this->blockDecoratorFactories[] = $factory;

        return $this;
    }

    public function registerBlockDecorator(BlockDecorator $decorator): static
    {
        return $this->registerBlockDecoratorFactory(fn () => $decorator);
    }

    public function removeBlockDecoratorFactory(int $index): static
    {
        if (isset($this->blockDecoratorFactories[$index])) {
            array_splice($this->blockDecoratorFactories, $index, 1);
        }

        return $this;
    }

    public function clearBlockDecoratorFactories(): static
    {
        $this->blockDecoratorFactories = [];

        return $this;
    }

    /**
     * @return list<callable(): BlockDecorator>
     */
    public function getBlockDecoratorFactories(): array
    {
        return $this->blockDecoratorFactories;
    }

    /**
     * @param  array<int, array<int, Token>>  $lines
     */
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

    /**
     * @throws InvalidJsonException
     */
    /**
     * @param  array<int, array<int, Token>>  $tokens
     * @return array<int, array<int, Token>>
     */
    protected function parseAnnotationTokens(array $tokens): array
    {
        if ($this->isJson() || $this->isPlainText()) {
            $processedTokens = $this->processTextOrJson($tokens);
        } else {
            $processedTokens = $this->processLanguage($tokens);
        }

        /** @var array<int, array<int, Token>> $processedTokens */
        $this->state->cleanedText = $this->extractText($processedTokens);

        return $processedTokens;
    }

    /**
     * @internal
     */
    /**
     * @return array<int, array<int, Token>>
     */
    public function getTokens(string $code, string|Grammar|ParsedGrammar $grammar): array
    {
        $languageName = is_string($grammar) ? $grammar : null;

        /** @var ParsedGrammar $grammar */
        $grammar = $this->environment->grammars->resolve($grammar);

        if ($languageName === null) {
            $languageName = $grammar->name;
        }

        if ($languageName === null) {
            throw new InvalidArgumentException('Unable to resolve language name.');
        }

        $this->state->resolvedGrammar = $grammar;
        $this->state->resolvedLanguageName = $languageName;

        $this->state->activeScopeName = $grammar->scopeName;

        $tokenizer = new Tokenizer($grammar, $this->environment);

        /** @var array<int, array<int, Token>> $tokenLines */
        $tokenLines = $tokenizer->tokenize($code);

        return $tokenLines;
    }

    /**
     * @throws InvalidJsonException
     */
    public function processCode(string $code, string|Grammar|ParsedGrammar $grammar): ProcessedTokens
    {
        if (is_string($grammar) && ! $grammar) {
            $grammar = 'text';
        }

        $tokens = $this->getTokens($code, $grammar);

        /** @var array<int, array<int, Token>> $tokens */
        $tokens = TokenMerger::merge($tokens);

        $resolvedGrammar = $this->state->resolvedGrammar;
        if ($resolvedGrammar === null) {
            throw new InvalidArgumentException('Resolved grammar is required before preprocessing.');
        }

        $tokens = $this->preprocess($tokens, $code, $resolvedGrammar, $this->state->resolvedLanguageName);

        $tokens = $this->parseAnnotationTokens($tokens);

        return new ProcessedTokens(
            tokens: $tokens,
            cleanedText: $this->state->cleanedText,
            grammar: $this->state->resolvedGrammar,
            languageName: $this->state->resolvedLanguageName,
            scopeName: $this->state->activeScopeName,
        );
    }

    /**
     * @param  array<string, ParsedTheme>  $themes
     */
    private function makeHighlighter(array $themes): Highlighter
    {
        return new Highlighter($themes);
    }

    /**
     * @param  list<array{0:int, 1:int}>  $ranges
     */
    protected function createAnnotationsFromOptions(string $annotationName, array $ranges): static
    {
        foreach ($ranges as $range) {
            [$start, $end] = $range;

            $annotation = new ParsedAnnotation;
            $annotation->index = count($this->state->parsedAnnotations);
            $annotation->sourceLine = $start;
            $annotation->name = $annotationName;
            $annotation->type = AnnotationType::Named;

            $annotationRange = new AnnotationRange;
            $annotationRange->type = RangeType::Relative;
            $annotationRange->start = null;
            $annotationRange->end = ($end - $start);

            $annotation->range = $annotationRange;

            $this->state->parsedAnnotations[] = $annotation;
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
            ->createAnnotationsFromOptions('mono', $this->torchlightOptions->monoLines)
            ->createAnnotationsFromOptions('hide', $this->torchlightOptions->hideLines);
    }

    /**
     * @param  string|PhikiTheme|ParsedTheme|Theme|array<int|string, string|PhikiTheme|ParsedTheme|Theme>  $theme
     */
    public function renderCode(string $code, Grammar|string $grammar, PhikiTheme|ParsedTheme|Theme|array|string $theme): RenderedBlock
    {
        $block = $this->buildRenderedBlock($code, $grammar, $theme);
        $block->code = $this->applyReplacers($block->code);

        return $block;
    }

    /**
     * @param  string|PhikiTheme|ParsedTheme|Theme|array<int|string, string|PhikiTheme|ParsedTheme|Theme>  $theme
     */
    public function codeToHtml(string $code, Grammar|string $grammar, PhikiTheme|ParsedTheme|Theme|array|string $theme): string
    {
        return $this->applyReplacers(
            $this->buildRenderedBlock($code, $grammar, $theme)->toHtml()
        );
    }

    /**
     * @throws InvalidJsonException
     */
    /**
     * @param  string|PhikiTheme|ParsedTheme|Theme|array<int|string, string|PhikiTheme|ParsedTheme|Theme>  $theme
     */
    private function buildRenderedBlock(string $code, Grammar|string $grammar, PhikiTheme|ParsedTheme|Theme|array|string $theme, bool $withGutter = false): RenderedBlock
    {
        $this->torchlightOptions = $this->userBaseOptions ?? Options::default();

        if ($withGutter) {
            /** @var array<string, mixed> $gutterOptions */
            $gutterOptions = ['withGutter' => true];
            $this->torchlightOptions = $this->torchlightOptions->mergeWith($gutterOptions);
        }

        $this->beginNewRender();

        $prepared = $this->prepareGrammar($code, $grammar);
        $grammar = $prepared->grammar;
        $this->state->languageVanityLabel = $prepared->vanityLabel;

        if (is_string($theme) && str_contains($theme, ':')) {
            $theme = Options::adjustOptionThemes([$theme]);
        }

        $code = rtrim($code);

        $tokens = $this->processCode($code, $grammar)->tokens;
        $themes = $this->wrapThemes($this->state->overrideThemes ?? $theme);

        $this->createAllAnnotationsFromOptions();
        $highlighter = $this->makeHighlighter($themes);

        $generator = $this->makeGenerator(
            $prepared->getName(),
            $themes,
            $highlighter,
        );

        $parsedAnnotations = array_values($this->state->parsedAnnotations);
        /** @var array<int, array<int, Token>> $annotatableTokens */
        $annotatableTokens = $tokens;

        $tokens = $this->annotationEngine
            ->setHighlighter($highlighter)
            ->setTorchlightOptions($this->torchlightOptions)
            ->process(
                $parsedAnnotations,
                $annotatableTokens,
            );

        $generator
            ->setTorchlightOptions($this->torchlightOptions);

        /** @var array<int, array<int, HighlightedToken>> $highlightedTokens */
        $highlightedTokens = $highlighter->highlight($tokens);

        return $generator->renderBlock($highlightedTokens);
    }

    /**
     * @return ParsedAnnotation[]
     */
    public function getParsedAnnotations(): array
    {
        return $this->state->parsedAnnotations;
    }

    /**
     * @param  string|PhikiTheme|ParsedTheme|Theme|array<int|string, string|PhikiTheme|ParsedTheme|Theme>  $themes
     * @return array<string, ParsedTheme>
     */
    protected function wrapThemes(string|array|PhikiTheme|ParsedTheme|Theme $themes): array
    {
        if (! is_array($themes)) {
            $themes = ['default' => $themes];
        }

        if (count($themes) === 1 && ! is_string(array_keys($themes)[0])) {
            $themes = ['light' => $themes[0]];
        }

        $wrappedThemes = [];

        foreach ($themes as $themeId => $theme) {
            $wrappedThemes[(string) $themeId] = $this->resolveTheme($theme);
        }

        return $wrappedThemes;
    }

    protected function resolveTheme(string|PhikiTheme|ParsedTheme|Theme $theme): ParsedTheme
    {
        if ($theme instanceof Theme) {
            return $theme->resolve(
                fn (string|PhikiTheme $themeName): ParsedTheme => $this->environment->themes->resolve($themeName)
            );
        }

        return $this->environment->themes->resolve($theme);
    }
}
