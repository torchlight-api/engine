<?php

namespace Torchlight\Engine\Annotations\Ranges;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class RangeResolver
{
    /**
     * @var ParsedAnnotation[]
     */
    protected array $annotations = [];

    protected int $maxLine = 0;

    protected array $annotationRanges = [];

    public function reset(): static
    {
        $this->annotations = [];
        $this->maxLine = 0;
        $this->annotationRanges = [];

        return $this;
    }

    public function setMaxLine(int $maxLine): static
    {
        $this->maxLine = $maxLine;

        return $this;
    }

    public function setAnnotations(array $annotations): static
    {
        $this->annotations = $annotations;

        return $this;
    }

    protected function makeSingleLineRange(int $lineNumber): ImpactedRange
    {
        return new ImpactedRange(true, $lineNumber, $lineNumber);
    }

    protected function makeRelativeRange(ParsedAnnotation $annotation): ?ImpactedRange
    {
        $range = clone $annotation->range;

        $startingLine = $annotation->sourceLine;

        $startingLine += intval($range->start ?? 0);
        $endingLine = $startingLine + intval($range->end);

        if ($startingLine != $annotation->sourceLine) {
            $endingLine -= 1;
        } else {
            if (intval($range->end) < 0) {
                $endingLine -= 1;

                if ($endingLine === 0) {
                    $endingLine = 1;
                }
            }
        }

        $endingLine = max($endingLine, 0);

        // Bail if both the end and start lines exceed the line count.
        if ($endingLine > $this->maxLine && $startingLine > $this->maxLine) {
            return null;
        }

        // Adjust for negatives.
        $startingLine = max(1, $startingLine);

        // Ensure end is within line count.
        $endingLine = min($this->maxLine, $endingLine);

        if ($endingLine < $startingLine) {
            if ($startingLine > $this->maxLine) {
                $startingLine = $endingLine;
                $endingLine = $this->maxLine;

                return new ImpactedRange($startingLine == $endingLine, $startingLine, $endingLine);
            }

            if ($annotation->range->start === null) {
                return new ImpactedRange($startingLine === $endingLine, $endingLine, $startingLine);
            }

            return null;
        }

        return new ImpactedRange($startingLine == $endingLine, $startingLine, $endingLine);
    }

    protected function resolveOpenEndedRange(ParsedAnnotation $start, ?ParsedAnnotation $end): ImpactedRange
    {
        $endingLine = $this->maxLine;

        if ($end != null) {
            $endingLine = $end->sourceLine;
        }

        return new ImpactedRange($start->sourceLine == $endingLine, $start->sourceLine, $endingLine);
    }

    protected function findRangeEnd(ParsedAnnotation $start): ?ParsedAnnotation
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation->index <= $start->index) {
                continue;
            }

            if ($annotation->range == null || $annotation->range->type != RangeType::OpenEndedEnd) {
                continue;
            }

            if ($annotation->name === $start->name) {
                return $annotation;
            }
        }

        return null;
    }

    public function resolve(ParsedAnnotation $annotation): ?ImpactedRange
    {
        $range = null;

        if ($annotation->range === null) {
            $range = $this->makeSingleLineRange($annotation->sourceLine);
        } elseif ($annotation->range->type == RangeType::Relative) {
            $range = $this->makeRelativeRange($annotation);
        } elseif ($annotation->range->type == RangeType::OpenEndedStart) {
            $range = $this->resolveOpenEndedRange(
                $annotation,
                $this->findRangeEnd($annotation)
            );
        } elseif ($annotation->range->type == RangeType::All) {
            return new ImpactedRange($this->maxLine === 1, 1, $this->maxLine);
        } elseif ($annotation->range->type == RangeType::Character) {
            return $this->makeSingleLineRange($annotation->sourceLine);
        }

        return $range;
    }
}
