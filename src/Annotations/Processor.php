<?php

namespace Torchlight\Engine\Annotations;

use Closure;
use Phiki\Highlighter;
use Torchlight\Engine\Annotations\Attributes\CssClassAnnotation;
use Torchlight\Engine\Annotations\Attributes\IdAnnotation;
use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Annotations\Ranges\RangeResolver;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Generators\Concerns\AddsScopesToTokens;
use Torchlight\Engine\Generators\GenerationOptions;
use Torchlight\Engine\Generators\Gutters\AbstractGutter;
use Torchlight\Engine\Generators\Gutters\CollapseGutter;
use Torchlight\Engine\Generators\Gutters\DiffGutter;
use Torchlight\Engine\Generators\Gutters\LineNumbersGutter;
use Torchlight\Engine\Options;
use Torchlight\Engine\Support\Str;

class Processor
{
    use AddsScopesToTokens;

    const ANNOTATION_HTML_CSS_CLASS = '*html-css-class';

    const ANNOTATION_HTML_ID_ATTRIBUTE = '*html-id-attribute';

    protected GenerationOptions $generationOptions;

    /**
     * @var AbstractAnnotation[]
     */
    protected array $annotations = [];

    protected ?ImpactedRange $activeRange = null;

    protected RangeResolver $rangeResolver;

    protected Options $options;

    protected array $currentTokens = [];

    protected ?Highlighter $highlighter = null;

    public function __construct()
    {
        $this->rangeResolver = new RangeResolver;
        $this->generationOptions = new GenerationOptions;

        $this->addDefaultGutters();

        $this->options = Options::default();

        $this->addAnnotation(static::ANNOTATION_HTML_CSS_CLASS, new CssClassAnnotation($this))
            ->addAnnotation(static::ANNOTATION_HTML_ID_ATTRIBUTE, new IdAnnotation($this));
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        $this->highlighter = $highlighter;

        foreach ($this->generationOptions->gutters as $gutter) {
            $gutter->setHighlighter($this->highlighter);
        }

        foreach ($this->annotations as $annotation) {
            $annotation->setHighlighter($this->highlighter);
        }

        return $this;
    }

    protected function addDefaultGutters(): void
    {
        $this->addGutter('line-numbers', new LineNumbersGutter($this))
            ->addGutter('diff', new DiffGutter($this))
            ->addGutter('collapse', new CollapseGutter($this));
    }

    public function collapseGutter(): CollapseGutter
    {
        return $this->generationOptions->gutters['collapse'];
    }

    public function lineNumbersGutter(): LineNumbersGutter
    {
        return $this->generationOptions->gutters['line-numbers'];
    }

    public function diffGutter(): DiffGutter
    {
        return $this->generationOptions->gutters['diff'];
    }

    public function addGutter(string $name, AbstractGutter $gutter): static
    {
        $this->generationOptions->gutters[$name] = $gutter;

        if ($this->highlighter) {
            $gutter->setHighlighter($this->highlighter);
        }

        return $this;
    }

    /**
     * @return \Torchlight\Engine\Generators\Gutters\AbstractGutter[]
     */
    public function getGutters(): array
    {
        return $this->generationOptions->gutters;
    }

    public function reindexLine(int $originalLine, ?int $newLine, ?int $relativeOffset = null): static
    {
        $this->lineNumbersGutter()->reindexLine($originalLine, $newLine, $relativeOffset);

        return $this;
    }

    public function reset(): static
    {
        $this->generationOptions->reset();

        return $this;
    }

    public function addBlockClass(string $class): static
    {
        $this->generationOptions->blockClasses[$class] = 1;

        return $this;
    }

    public function addClassToCharacterRange(int $line, int $start, int $end, string $class): static
    {
        return $this->addAttributesToCharacterRange($line, $start, $end, [
            'class' => $class,
        ]);
    }

    public function addIdToCharacterRange(int $line, int $start, int $end, string $id): static
    {
        return $this->addAttributesToCharacterRange($line, $start, $end, [
            'id' => $id,
        ]);
    }

    protected function addAttributesToCharacterRange(int $line, int $start, int $end, array $attributes): static
    {
        $line -= 1;

        if (! array_key_exists($line, $this->generationOptions->characterDecorators)) {
            $this->generationOptions->characterDecorators[$line] = [];
        }

        $attributes['start'] = $start - 1;
        $attributes['end'] = $end - 1;

        $this->generationOptions->characterDecorators[$line][] = $attributes;

        return $this;
    }

    public function addLineClass(int $line, string $class): static
    {
        if (! array_key_exists($line, $this->generationOptions->lineClasses)) {
            $this->generationOptions->lineClasses[$line] = [];
        }

        $this->generationOptions->lineClasses[$line][] = $class;

        return $this;
    }

    public function addAttributesToLine(int $line, array $attributes): static
    {
        foreach ($attributes as $attribute) {
            [$name, $value] = $attribute;

            $this->addAttributeToLine($line, $name, $value);
        }

        return $this;
    }

    public function addAttributeToLine(int $line, string $name, string $value): static
    {
        if (! array_key_exists($line, $this->generationOptions->lineAttributes)) {
            $this->generationOptions->lineAttributes[$line] = [];
        }

        $this->generationOptions->lineAttributes[$line][$name] = $value;

        return $this;
    }

    public function addScopeToLine(int $line, array|string $scope): static
    {
        $line -= 1;

        if (! is_array($scope)) {
            $scope = [$scope];
        }

        $this->currentTokens = $this->addScopesToTokens($line, $this->currentTokens, $scope);

        return $this;
    }

    public function prependLine(int $line, string $content): static
    {
        if (! array_key_exists($line, $this->generationOptions->linePrepends)) {
            $this->generationOptions->linePrepends[$line] = [];
        }

        $this->generationOptions->linePrepends[$line][] = $content;

        return $this;
    }

    public function appendLine(int $line, string $content): static
    {
        if (! array_key_exists($line, $this->generationOptions->lineAppends)) {
            $this->generationOptions->lineAppends[$line] = [];
        }

        $this->generationOptions->lineAppends[$line][] = $content;

        return $this;
    }

    public function surroundLine(int $line, string $prefix, string $suffix): static
    {
        return $this->prependLine($line, $prefix)
            ->appendLine($line, $suffix);
    }

    public function surroundRange(ImpactedRange $range, string $prefix, string $suffix): static
    {
        return $this->prependLine($range->startLine, $prefix)
            ->appendLine($range->endLine, $suffix);
    }

    public function safeReplace(string $search, string $replace, string $subject): string
    {
        if (! str_contains($subject, $search)) {
            return $subject;
        }

        $replacement = Str::random(24);

        $this->generationOptions->textReplacements[$replacement] = $replace;

        return str_replace($search, $replacement, $subject);
    }

    public function modifyLineContents(int $line, callable|Closure $callback): static
    {
        if (! array_key_exists($line, $this->generationOptions->lineContentCallbacks)) {
            $this->generationOptions->lineContentCallbacks[$line] = [];
        }

        $this->generationOptions->lineContentCallbacks[$line][] = $callback;

        return $this;
    }

    public function modifyLineTokens(int $line, callable|Closure $callback): static
    {
        if (! array_key_exists($line, $this->generationOptions->lineTokenCallbacks)) {
            $this->generationOptions->lineTokenCallbacks[$line] = [];
        }

        $this->generationOptions->lineTokenCallbacks[$line][] = $callback;

        return $this;
    }

    public function addAnnotation(string $name, AbstractAnnotation $annotation): static
    {
        $this->annotations[$name] = $annotation;

        if ($this->highlighter) {
            $annotation->setHighlighter($this->highlighter);
        }

        return $this;
    }

    /**
     * @return AbstractAnnotation[]
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    public function getGenerationOptions(): GenerationOptions
    {
        return $this->generationOptions;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->options = $options;

        foreach ($this->generationOptions->gutters as $gutter) {
            $gutter->setTorchlightOptions($options);
        }

        return $this;
    }

    protected function getAnnotationName(ParsedAnnotation $annotation): string
    {
        if ($annotation->type == AnnotationType::ClassName) {
            return static::ANNOTATION_HTML_CSS_CLASS;
        } elseif ($annotation->type == AnnotationType::IdAttribute) {
            return static::ANNOTATION_HTML_ID_ATTRIBUTE;
        }

        return $annotation->name;
    }

    public function process(array $parsedAnnotations, array $tokens): array
    {
        $this->currentTokens = $tokens;

        $lineCount = count($tokens);

        $this->rangeResolver
            ->reset()
            ->setAnnotations($parsedAnnotations)
            ->setMaxLine($lineCount);

        $this->lineNumbersGutter()->setMaxLineCount($lineCount);

        foreach ($this->annotations as $annotation) {
            $annotation->beforeProcess();
        }

        /** @var \Torchlight\Engine\Annotations\Parser\ParsedAnnotation $annotation */
        foreach ($parsedAnnotations as $annotation) {
            if ($annotation->range && $annotation->range->type == RangeType::OpenEndedEnd) {
                continue;
            }

            $annotationName = $this->getAnnotationName($annotation);

            if (! array_key_exists($annotationName, $this->annotations)) {
                continue;
            }

            $this->activeRange = $this->rangeResolver->resolve($annotation);

            if ($this->activeRange === null) {
                continue;
            }

            $this->annotations[$annotationName]
                ->setTorchlightOptions($this->options)
                ->setActiveRange($this->activeRange)
                ->setParsedAnnotation($annotation)
                ->process($annotation);
        }

        foreach ($this->annotations as $annotation) {
            $annotation->afterProcess();
        }

        $tokens = $this->currentTokens;

        $this->currentTokens = [];

        return $tokens;
    }
}
