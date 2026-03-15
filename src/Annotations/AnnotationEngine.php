<?php

namespace Torchlight\Engine\Annotations;

use Closure;
use LogicException;
use Phiki\Highlighting\Highlighter;
use Phiki\Token\Token;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Annotations\Ranges\RangeResolver;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Generators\GenerationOptions;
use Torchlight\Engine\Generators\Gutters\AbstractGutter;
use Torchlight\Engine\Generators\Gutters\CollapseGutter;
use Torchlight\Engine\Generators\Gutters\CustomContentGutter;
use Torchlight\Engine\Generators\Gutters\DiffGutter;
use Torchlight\Engine\Generators\Gutters\LineNumbersGutter;
use Torchlight\Engine\Options;
use Torchlight\Engine\Support\Str;

class AnnotationEngine
{
    protected GenerationOptions $generationOptions;

    protected RangeResolver $rangeResolver;

    protected Options $options;

    /** @var array<int, array<int, Token>> */
    protected array $currentTokens = [];

    protected ?Highlighter $highlighter = null;

    public function __construct(protected AnnotationRegistry $registry = new AnnotationRegistry)
    {
        $this->rangeResolver = new RangeResolver;
        $this->generationOptions = new GenerationOptions;

        $this->addDefaultGutters();

        $this->options = Options::default();
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        $this->highlighter = $highlighter;

        $this->registry->setHighlighter($highlighter);

        return $this;
    }

    protected function addDefaultGutters(): void
    {
        $this->addGutter('line-numbers', (new LineNumbersGutter($this))->setPriority(100))
            ->addGutter('diff', (new DiffGutter($this))->setPriority(200))
            ->addGutter('custom-content', (new CustomContentGutter($this))->setPriority(300))
            ->addGutter('collapse', (new CollapseGutter($this))->setPriority(400));
    }

    public function collapseGutter(): CollapseGutter
    {
        $gutter = $this->generationOptions->gutters['collapse'] ?? null;

        if (! $gutter instanceof CollapseGutter) {
            throw new LogicException('Collapse gutter is not configured.');
        }

        return $gutter;
    }

    public function lineNumbersGutter(): LineNumbersGutter
    {
        $gutter = $this->generationOptions->gutters['line-numbers'] ?? null;

        if (! $gutter instanceof LineNumbersGutter) {
            throw new LogicException('Line numbers gutter is not configured.');
        }

        return $gutter;
    }

    public function diffGutter(): DiffGutter
    {
        $gutter = $this->generationOptions->gutters['diff'] ?? null;

        if (! $gutter instanceof DiffGutter) {
            throw new LogicException('Diff gutter is not configured.');
        }

        return $gutter;
    }

    public function customContentGutter(): CustomContentGutter
    {
        $gutter = $this->generationOptions->gutters['custom-content'] ?? null;

        if (! $gutter instanceof CustomContentGutter) {
            throw new LogicException('Custom content gutter is not configured.');
        }

        return $gutter;
    }

    public function addGutter(string $name, AbstractGutter $gutter): static
    {
        $this->generationOptions->gutters[$name] = $gutter;

        if ($this->generationOptions->gutterServices !== null) {
            $gutter
                ->setServices($this->generationOptions->gutterServices)
                ->setGenerationOptions($this->generationOptions);
        }

        return $this;
    }

    /**
     * Remove a gutter by name.
     */
    public function removeGutter(string $name): static
    {
        unset($this->generationOptions->gutters[$name]);

        return $this;
    }

    public function hasGutter(string $name): bool
    {
        return isset($this->generationOptions->gutters[$name]);
    }

    /**
     * @return AbstractGutter[]
     */
    public function getGutters(): array
    {
        return $this->generationOptions->gutters;
    }

    public function setGutterPriority(string $name, int $priority): static
    {
        if (isset($this->generationOptions->gutters[$name])) {
            $this->generationOptions->gutters[$name]->setPriority($priority);
        }

        return $this;
    }

    public function placeGutterAfter(string $gutter, string $afterGutter): static
    {
        if (isset($this->generationOptions->gutters[$afterGutter])) {
            $refPriority = $this->generationOptions->gutters[$afterGutter]->getPriority();
            $this->setGutterPriority($gutter, $refPriority + 1);
        }

        return $this;
    }

    public function placeGutterBefore(string $gutter, string $beforeGutter): static
    {
        if (isset($this->generationOptions->gutters[$beforeGutter])) {
            $refPriority = $this->generationOptions->gutters[$beforeGutter]->getPriority();
            $this->setGutterPriority($gutter, $refPriority - 1);
        }

        return $this;
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

    /**
     * @param  array<string, string>  $attributes
     */
    public function addAttributesToCharacterRange(int $line, int $start, int $end, array $attributes): static
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

    /**
     * @param  array<string, string>  $attributes
     */
    public function addAttributesToLine(int $line, array $attributes): static
    {
        foreach ($attributes as $name => $value) {
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

    /**
     * @param  string|list<string>  $scope
     */
    public function addScopeToLine(int $line, array|string $scope): static
    {
        $line -= 1;

        if (! is_array($scope)) {
            $scope = [$scope];
        }

        $scope = array_values(array_filter($scope, is_string(...)));

        $this->currentTokens = $this->addScopesToTokens($line, $this->currentTokens, $scope);

        return $this;
    }

    /**
     * @param  array<int, array<int, Token>>  $tokens
     * @param  list<string>  $scopes
     * @return array<int, array<int, Token>>
     */
    private function addScopesToTokens(int $line, array $tokens, array $scopes): array
    {
        if (! array_key_exists($line, $tokens)) {
            return $tokens;
        }

        $lineTokens = $tokens[$line];

        for ($i = 0; $i < count($lineTokens); $i++) {
            foreach ($scopes as $scope) {
                $lineTokens[$i]->scopes[] = $scope;
            }
        }

        $tokens[$line] = $lineTokens;

        return $tokens;
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

    public function removeLine(int $line): static
    {
        $this->generationOptions->removedLines[$line] = true;

        return $this;
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

    /**
     * Get the text content of a specific line from the current tokens.
     * Line is 1-based.
     */
    public function getLineText(int $line): ?string
    {
        $index = $line - 1;

        if (! array_key_exists($index, $this->currentTokens)) {
            return null;
        }

        $text = '';
        /** @var Token $token */
        foreach ($this->currentTokens[$index] as $token) {
            $text .= $token->text;
        }

        return $text;
    }

    public function getLineCount(): int
    {
        return count($this->currentTokens);
    }

    public function getRegistry(): AnnotationRegistry
    {
        return $this->registry;
    }

    public function addAnnotation(string $name, AbstractAnnotation $annotation): static
    {
        $this->registry->register($name, $annotation);

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
        return $this->registry->all();
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

    protected function resolveAnnotationHandler(ParsedAnnotation $annotation): ?AbstractAnnotation
    {
        return $this->registry->resolve($annotation->name);
    }

    /**
     * @param  array<int, ParsedAnnotation>  $parsedAnnotations
     * @param  array<int, array<int, Token>>  $tokens
     * @return array<int, array<int, Token>>
     */
    public function process(array $parsedAnnotations, array $tokens): array
    {
        $this->currentTokens = $tokens;

        $lineCount = count($tokens);

        $this->rangeResolver
            ->reset()
            ->setAnnotations($parsedAnnotations)
            ->setMaxLine($lineCount);

        $this->lineNumbersGutter()->setMaxLineCount($lineCount);

        foreach ($this->registry->allIncludingPrefixHandlers() as $annotation) {
            $annotation->beforeProcess();
        }

        /** @var ParsedAnnotation $parsedAnnotation */
        foreach ($parsedAnnotations as $parsedAnnotation) {
            if ($parsedAnnotation->range && $parsedAnnotation->range->type == RangeType::OpenEndedEnd) {
                continue;
            }

            $handler = $this->resolveAnnotationHandler($parsedAnnotation);

            if ($handler === null) {
                continue;
            }

            $activeRange = $this->rangeResolver->resolve($parsedAnnotation);

            if ($activeRange === null) {
                continue;
            }

            $handler
                ->setTorchlightOptions($this->options)
                ->setActiveRange($activeRange)
                ->setParsedAnnotation($parsedAnnotation)
                ->process($parsedAnnotation);
        }

        foreach ($this->registry->allIncludingPrefixHandlers() as $annotation) {
            $annotation->afterProcess();
        }

        if ($this->options->diffIndicatorsInPlaceOfNumbers === false && $this->diffGutter()->hasMarkers()) {
            $this->generationOptions->hasSeparatePaddingGutter = true;
        }

        $removedLines = array_keys($this->generationOptions->removedLines);
        if (! empty($removedLines)) {
            $this->lineNumbersGutter()->adjustForRemovedLines($removedLines, $lineCount);
        }

        $tokens = $this->currentTokens;

        $this->currentTokens = [];

        return $tokens;
    }
}
