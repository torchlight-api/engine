<?php

namespace Torchlight\Engine\Annotations\Parser;

use Torchlight\Engine\Annotations\Ranges\AnnotationRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Support\Str;

class AnnotationTokenParser
{
    public const ANNOTATION_PATTERN = '/\[tl!([^]]*(?:\][^]]*)*)\]/';

    /**
     * @var string[]
     */
    protected array $annotationNames = [];

    protected array $annotations = [];

    protected int $annotationIndex = 0;

    public function reset(): static
    {
        $this->annotations = [];
        $this->annotationIndex = 0;

        return $this;
    }

    public function setAnnotationNames(array $names): static
    {
        $this->annotationNames = $names;

        return $this;
    }

    public function addAnnotationName(string $name): static
    {
        $this->annotationNames[] = $name;

        return $this;
    }

    protected function extractAnnotationName(string $text): string
    {
        $lastColonText = Str::afterLast($text, ':');
        $colonPos = strrpos($text, ':');

        if (! $this->isValidRange($lastColonText ?? '')) {
            $colonPos = INF;
        }

        $leftParenPos = INF;

        if (! str_starts_with($text, '.')) {
            $leftParenPos = mb_strpos($text, '(');

            if (! $colonPos) {
                $colonPos = INF;
            }
            if (! $leftParenPos) {
                $leftParenPos = INF;
            }
        }

        $loc = min($colonPos, $leftParenPos);

        if (! is_infinite($loc)) {
            $text = mb_substr($text, 0, $loc);
        }

        return $text;
    }

    protected function isAnnotation(string $text): bool
    {
        if (str_starts_with($text, '.') || str_starts_with($text, '#')) {
            return true;
        }

        return in_array(mb_strtolower($text), $this->annotationNames);
    }

    protected function getAnnotationType(string $name): AnnotationType
    {
        if (str_starts_with($name, '.')) {
            return AnnotationType::ClassName;
        }

        if (str_starts_with($name, '#')) {
            return AnnotationType::IdAttribute;
        }

        return AnnotationType::Named;
    }

    protected function parseRange(string $text): ?AnnotationRange
    {
        $text = Str::afterLast($text, ':');

        if (mb_strlen(trim($text)) === 0) {
            return null;
        }

        $range = new AnnotationRange;

        if (str_contains($text, ',')) {
            $parts = explode(',', $text, 2);

            $range->type = RangeType::Relative;
            $range->start = $parts[0];
            $range->end = $parts[1];

            if (str_starts_with($range->start, 'c')) {
                $range->start = mb_substr($range->start, 1);
                $range->type = RangeType::Character;
            }

            return $range;
        }

        if ($text === 'all') {
            $range->type = RangeType::All;

            return $range;
        }

        if (! in_array($text, ['start', 'end'])) {
            $range->type = RangeType::Relative;
            $range->end = $text;

            return $range;
        }

        $range->type = ($text == 'start') ? RangeType::OpenEndedStart : RangeType::OpenEndedEnd;

        return $range;
    }

    protected function parseMethodArgs(string $text): ?string
    {
        if (! str_contains($text, ')')) {
            return null;
        }

        return Str::after(
            Str::beforeLast($text, ')'),
            '('
        );
    }

    private function isValidRange(string $text): bool
    {
        return str_contains($text, ',') ||
            in_array($text, ['start', 'end', 'all']) ||
            is_numeric($text);
    }

    protected function convertAnnotations(array $annotations, int $sourceLine): array
    {
        $results = [];

        foreach ($annotations as $tmpAnnotation) {
            $annotationText = implode(' ', $tmpAnnotation);

            $annotation = new ParsedAnnotation;
            $annotation->sourceLine = $sourceLine;
            $annotation->index = $this->annotationIndex;

            $tmpName = array_shift($tmpAnnotation);

            $name = $this->extractAnnotationName($tmpName);

            $annotation->text = $annotationText;
            $annotation->type = $this->getAnnotationType($name);
            $annotation->name = $name;

            if (str_contains($tmpName, ':') && $this->isValidRange(Str::afterLast($tmpName, ':'))) {
                $annotation->range = $this->parseRange($tmpName);
            }

            if (str_contains($tmpName, '(')) {
                $annotation->methodArgs = $this->parseMethodArgs($tmpName);
            }

            $annotation->options = $tmpAnnotation;

            $this->annotations[] = $annotation;
            $results[] = $annotation;
            $this->annotationIndex++;
        }

        return $results;
    }

    protected function parseAnnotations(string $text, int $sourceLine): array
    {
        $parts = explode(' ', trim($text));
        $tmpAnnotations = [];
        $annotationParts = [];
        $annotationPart = null;
        $annotationName = null;

        foreach ($parts as $part) {
            $checkName = $this->extractAnnotationName($part);

            if ($this->isAnnotation($checkName)) {
                if (
                    ($annotationName != null && $checkName != $annotationName) ||
                    count($annotationParts) > 0
                ) {
                    if ($annotationPart != null) {
                        array_unshift($annotationParts, $annotationPart);
                    }

                    $tmpAnnotations[] = $annotationParts;

                    $annotationParts = [];
                }

                $annotationName = $checkName;
                $annotationPart = $part;

                continue;
            }

            $annotationParts[] = $part;
        }

        if ($annotationPart != null || count($annotationParts) > 0) {
            if ($annotationPart != null) {
                array_unshift($annotationParts, $annotationPart);
            }

            $tmpAnnotations[] = $annotationParts;
        }

        $finalAnnotations = [];

        foreach ($tmpAnnotations as $tmpAnnotation) {
            if (str_starts_with($tmpAnnotation[0], '.') || str_starts_with($tmpAnnotation[0], '#')) {
                foreach ($this->parseCombinedClassIdAnnotations($tmpAnnotation) as $annotation) {
                    $finalAnnotations[] = $annotation;
                }

                continue;
            }

            $finalAnnotations[] = $tmpAnnotation;
        }

        return $this->convertAnnotations($finalAnnotations, $sourceLine);
    }

    protected function parseCombinedClassIdAnnotations(array $annotation): array
    {
        $results = [];
        $value = array_shift($annotation);

        $pattern = '/([.#])([^.#]+)/';

        preg_match_all($pattern, $value, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $results[] = array_merge(
                [$match[0]],
                $annotation
            );
        }

        return $results;
    }

    public function parseText(string $text, int $sourceLine): ParseResult
    {
        $parseResult = new ParseResult;

        $parseResult->text = preg_replace(static::ANNOTATION_PATTERN, '', $text);

        preg_match_all(self::ANNOTATION_PATTERN, $text, $matches);

        foreach ($matches[1] as $match) {
            $parseResult->annotations = array_merge(
                $parseResult->annotations,
                $this->parseAnnotations($match, $sourceLine),
            );
        }

        return $parseResult;
    }

    public function getAnnotations(): array
    {
        return $this->annotations;
    }
}
