<?php

namespace Torchlight\Engine\Annotations;

use Closure;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Generators\Concerns\InteractsWithHtmlRenderer;
use Torchlight\Engine\Generators\Gutters\CollapseGutter;
use Torchlight\Engine\Generators\Gutters\DiffGutter;
use Torchlight\Engine\Generators\Gutters\LineNumbersGutter;
use Torchlight\Engine\Options;

abstract class AbstractAnnotation
{
    use InteractsWithHtmlRenderer;

    protected Options $options;

    protected ?ImpactedRange $range = null;

    protected ?ParsedAnnotation $parsedAnnotation = null;

    public static string $name = '';

    public static array $aliases = [];

    public function __construct(
        protected Processor $processor,
    ) {
        $this->options = Options::default();
    }

    public function reset(): void
    {
        $this->parsedAnnotation = null;
        $this->range = null;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->options = $options;

        return $this;
    }

    protected function addBlockClass(array|string $class): static
    {
        if (! is_array($class)) {
            $class = [$class];
        }

        foreach ($class as $className) {
            $this->processor->addBlockClass($className);
        }

        return $this;
    }

    private function eachLine(callable|Closure $callback): static
    {
        for ($i = $this->range->startLine; $i <= $this->range->endLine; $i++) {
            call_user_func_array($callback, [$i]);
        }

        return $this;
    }

    protected function addLineScope(array|string $scope): static
    {
        if (! is_array($scope)) {
            $scope = [$scope];
        }

        return $this->eachLine(function ($i) use ($scope) {
            $this->processor->addScopeToLine($i, $scope);
        });
    }

    protected function markLinesHighlighted(): static
    {
        return $this->eachLine(function ($i) {
            $this->lineNumbersGutter()->markLineHighlighted($i);
        });
    }

    protected function addClassToCharacterRange(string $class): static
    {
        $this->processor->addClassToCharacterRange(
            $this->range->startLine,
            intval($this->parsedAnnotation->range->start),
            intval($this->parsedAnnotation->range->end),
            $class
        );

        return $this;
    }

    protected function addIdToCharacterRange(string $id): static
    {
        $this->processor->addIdToCharacterRange(
            $this->range->startLine,
            intval($this->parsedAnnotation->range->start),
            intval($this->parsedAnnotation->range->end),
            $id
        );

        return $this;
    }

    protected function addLineClass(array|string $class): static
    {
        if (! is_array($class)) {
            $class = [$class];
        }

        return $this->eachLine(function ($i) use ($class) {
            foreach ($class as $className) {
                $this->processor->addLineClass($i, $className);
            }
        });
    }

    protected function addLineAttribute(string $name, string $value): static
    {
        return $this->eachLine(function ($i) use ($name, $value) {
            $this->processor->addAttributeToLine($i, $name, $value);
        });
    }

    protected function replaceLineMarker(string $marker, array $scopes = []): static
    {
        return $this->eachLine(function ($i) use ($marker, $scopes) {
            $this->lineNumbersGutter()->replaceLineMarker($i, [$marker, $scopes]);
        });
    }

    protected function setLineScopes(array $scopes): static
    {
        return $this->eachLine(function ($i) use ($scopes) {
            $this->lineNumbersGutter()->setLineScopes($i, $scopes);
        });
    }

    protected function setDiffLineMarker(string $marker): static
    {
        return $this->eachLine(function ($i) use ($marker) {
            $this->diffGutter()->setLineMarker($i, $marker);
        });
    }

    protected function prependLine(int $line, string $content): static
    {
        $this->processor->prependLine($line, $content);

        return $this;
    }

    protected function appendLine(int $line, string $content): static
    {
        $this->processor->appendLine($line, $content);

        return $this;
    }

    protected function reindexLine(int $originalLine, ?int $newLine, ?int $relativeOffset = null): static
    {
        $this->processor->reindexLine($originalLine, $newLine, $relativeOffset);

        return $this;
    }

    protected function forceDisplayLine(int $originalLine, int $newLine): static
    {
        $this->processor->lineNumbersGutter()->forceLineDisplay($originalLine, $newLine);

        return $this;
    }

    protected function surroundStartLine(string $prefix, string $suffix): static
    {
        return $this->prependLine($this->range->startLine, $prefix)
            ->appendLine($this->range->startLine, $suffix);
    }

    protected function surroundEndLine(string $prefix, string $suffix): static
    {
        return $this->prependLine($this->range->endLine, $prefix)
            ->appendLine($this->range->endLine, $suffix);
    }

    protected function surroundLine(int $line, string $prefix, string $suffix): static
    {
        $this->processor->surroundLine($line, $prefix, $suffix);

        return $this;
    }

    protected function surroundRange(string $prefix, string $suffix): static
    {
        $this->processor->surroundRange($this->range, $prefix, $suffix);

        return $this;
    }

    protected function modifyRangeTokens(callable|Closure $callback): static
    {
        $this->eachLine(function ($line) use ($callback) {
            $this->processor->modifyLineTokens($line, $callback);
        });

        return $this;
    }

    protected function modifyRangeContents(callable|Closure $callback): static
    {
        $this->eachLine(function ($line) use ($callback) {
            $this->modifyLineContents($line, $callback);
        });

        return $this;
    }

    public function safeReplace(string $search, string $replace, string $subject): string
    {
        return $this->processor->safeReplace($search, $replace, $subject);
    }

    protected function modifyLineContents(int $line, callable|Closure $callback): static
    {
        $this->processor->modifyLineContents($line, $callback);

        return $this;
    }

    protected function modifyStartLineContents(callable|Closure $callback): static
    {
        return $this->modifyLineContents($this->range->startLine, $callback);
    }

    public function setParsedAnnotation(ParsedAnnotation $annotation): static
    {
        $this->parsedAnnotation = $annotation;

        return $this;
    }

    public function setActiveRange(ImpactedRange $range): static
    {
        $this->range = $range;

        return $this;
    }

    protected function isCharacterRange(): bool
    {
        if ($this->parsedAnnotation === null || $this->parsedAnnotation->range == null) {
            return false;
        }

        return $this->parsedAnnotation->range->type == RangeType::Character;
    }

    protected function lineNumbersGutter(): LineNumbersGutter
    {
        return $this->processor->lineNumbersGutter();
    }

    protected function diffGutter(): DiffGutter
    {
        return $this->processor->diffGutter();
    }

    protected function collapseGutter(): CollapseGutter
    {
        return $this->processor->collapseGutter();
    }

    abstract public function process(ParsedAnnotation $annotation): void;

    public function beforeProcess(): void {}

    public function afterProcess(): void {}
}
