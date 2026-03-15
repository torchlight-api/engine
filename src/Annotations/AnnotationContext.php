<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;

class AnnotationContext
{
    public function __construct(
        protected AnnotationEngine $annotationEngine,
        protected ParsedAnnotation $annotation,
        protected ImpactedRange $range,
    ) {}

    public function getMethodArgs(): ?string
    {
        return $this->annotation->methodArgs;
    }

    /** @return array<int|string, mixed> */
    public function getOptions(): array
    {
        return $this->annotation->options;
    }

    public function isCharacterRange(): bool
    {
        if ($this->annotation->range === null) {
            return false;
        }

        return $this->annotation->range->type === RangeType::Character;
    }

    public function addBlockClass(string $class): static
    {
        $this->annotationEngine->addBlockClass($class);

        return $this;
    }

    /**
     * @param  string|list<string>  $class
     */
    public function addLineClass(array|string $class): static
    {
        if (! is_array($class)) {
            $class = [$class];
        }

        for ($i = $this->range->startLine; $i <= $this->range->endLine; $i++) {
            foreach ($class as $className) {
                $this->annotationEngine->addLineClass($i, $className);
            }
        }

        return $this;
    }

    public function addLineAttribute(string $name, string $value): static
    {
        for ($i = $this->range->startLine; $i <= $this->range->endLine; $i++) {
            $this->annotationEngine->addAttributeToLine($i, $name, $value);
        }

        return $this;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    public function addAttributesToCharacterRange(array $attributes): static
    {
        $range = $this->annotation->range;

        if ($range === null) {
            throw new \LogicException('Character-range annotation is required.');
        }

        $this->annotationEngine->addAttributesToCharacterRange(
            $this->range->startLine,
            intval($range->start),
            intval($range->end),
            $attributes
        );

        return $this;
    }

    public function addClassToCharacterRange(string $class): static
    {
        return $this->addAttributesToCharacterRange(['class' => $class]);
    }

    public function getLineText(int $line): ?string
    {
        return $this->annotationEngine->getLineText($line);
    }

    public function getStartLine(): int
    {
        return $this->range->startLine;
    }

    public function getEndLine(): int
    {
        return $this->range->endLine;
    }
}
