<?php

namespace Torchlight\Engine\Annotations\Parser;

use Torchlight\Engine\Annotations\Ranges\AnnotationRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Support\Str;

class AnnotationTokenParser
{
    public const ANNOTATION_PATTERN = '/\[tl!([^]]*(?:\](?!\s*\[tl!)[^]]*)*)\]/';

    /**
     * @var string[]
     */
    protected array $annotationNames = [];

    /**
     * Registered prefixes for prefix-based annotations.
     *
     * @var string[]
     */
    protected array $registeredPrefixes = ['.', '#'];

    /** @var list<ParsedAnnotation> */
    protected array $annotations = [];

    protected int $annotationIndex = 0;

    public function reset(): static
    {
        $this->annotations = [];
        $this->annotationIndex = 0;

        return $this;
    }

    /** @param list<string> $names */
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

    /** @param list<string> $prefixes */
    public function setRegisteredPrefixes(array $prefixes): static
    {
        $this->registeredPrefixes = array_values(array_unique($prefixes));

        return $this;
    }

    public function addRegisteredPrefix(string $prefix): static
    {
        if (! in_array($prefix, $this->registeredPrefixes, true)) {
            $this->registeredPrefixes[] = $prefix;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getPrefixesBySpecificity(): array
    {
        $prefixes = $this->registeredPrefixes;

        usort($prefixes, function (string $left, string $right): int {
            $lengthCompare = mb_strlen($right) <=> mb_strlen($left);

            return $lengthCompare !== 0 ? $lengthCompare : strcmp($left, $right);
        });

        return $prefixes;
    }

    protected function matchesPrefix(string $text): ?string
    {
        foreach ($this->getPrefixesBySpecificity() as $prefix) {
            if (str_starts_with($text, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }

    protected function extractAnnotationName(string $text): string
    {
        $lastColonText = Str::afterLast($text, ':');
        $colonPos = strrpos($text, ':');
        $colonPos = $colonPos === false ? PHP_INT_MAX : $colonPos;

        if (! $this->isValidRange($lastColonText)) {
            $colonPos = PHP_INT_MAX;
        }

        $leftParenPos = PHP_INT_MAX;

        // Only check for parentheses if this is not a prefix-based annotation
        if ($this->matchesPrefix($text) === null) {
            $leftParenPos = mb_strpos($text, '(');
            if ($leftParenPos === false) {
                $leftParenPos = PHP_INT_MAX;
            }
        }

        $loc = min($colonPos, $leftParenPos);

        if ($loc !== PHP_INT_MAX) {
            $text = mb_substr($text, 0, $loc);
        }

        return $text;
    }

    protected function isAnnotation(string $text): bool
    {
        if ($this->matchesPrefix($text) !== null) {
            return true;
        }

        return in_array(mb_strtolower($text), $this->annotationNames);
    }

    /**
     * @return array{AnnotationType, string|null} [type, matched_prefix]
     */
    protected function getAnnotationTypeAndPrefix(string $name): array
    {
        $matchedPrefix = $this->matchesPrefix($name);

        if ($matchedPrefix !== null) {
            return [AnnotationType::Prefixed, $matchedPrefix];
        }

        return [AnnotationType::Named, null];
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

    protected function extractMethodArgs(string $text): ?string
    {
        if (! str_contains($text, ')')) {
            return null;
        }

        return Str::after(
            Str::beforeLast($text, ')'),
            '('
        );
    }

    protected function parseMethodArgs(string $text): ?string
    {
        $args = $this->extractMethodArgs($text);

        if ($args === null) {
            return null;
        }

        if ((str_starts_with($args, '"') && str_ends_with($args, '"')) ||
            (str_starts_with($args, "'") && str_ends_with($args, "'"))) {
            $args = mb_substr($args, 1, -1);
        }

        return $args;
    }

    private function isValidRange(string $text): bool
    {
        return str_contains($text, ',') ||
            in_array($text, ['start', 'end', 'all']) ||
            is_numeric($text);
    }

    /**
     * @param  list<list<string>>  $annotations
     * @return list<ParsedAnnotation>
     */
    protected function convertAnnotations(array $annotations, int $sourceLine): array
    {
        $results = [];

        foreach ($annotations as $tmpAnnotation) {
            $annotationText = implode(' ', $tmpAnnotation);

            $annotation = new ParsedAnnotation;
            $annotation->sourceLine = $sourceLine;
            $annotation->index = $this->annotationIndex;

            $tmpName = array_shift($tmpAnnotation);
            if ($tmpName === null) {
                continue;
            }

            $name = $this->extractAnnotationName($tmpName);

            $annotation->text = $annotationText;

            [$type, $prefix] = $this->getAnnotationTypeAndPrefix($name);
            $annotation->type = $type;
            $annotation->prefix = $prefix;
            $annotation->name = $name;

            if (str_contains((string) $tmpName, ':') && $this->isValidRange(Str::afterLast($tmpName, ':'))) {
                $annotation->range = $this->parseRange($tmpName);
            }

            if (str_contains($annotationText, '(')) {
                $annotation->rawMethodArgs = $this->extractMethodArgs($annotationText);
                $annotation->methodArgs = $this->parseMethodArgs($annotationText);

                // When method args span spaces, the range suffix may be after the closing
                // paren in the full annotation text rather than in $tmpName alone.
                if ($annotation->range === null && str_contains($annotationText, ')')) {
                    $afterParen = Str::after($annotationText, ')');

                    if (str_starts_with($afterParen, ':') && $this->isValidRange(Str::afterLast($afterParen, ':'))) {
                        $annotation->range = $this->parseRange($afterParen);
                    }
                }
            }

            $annotation->options = $tmpAnnotation;

            $this->annotations[] = $annotation;
            $results[] = $annotation;
            $this->annotationIndex++;
        }

        return $results;
    }

    /** @return list<ParsedAnnotation> */
    protected function parseAnnotations(string $text, int $sourceLine): array
    {
        $parts = explode(' ', trim($text));
        $tmpAnnotations = [];
        $annotationParts = [];
        $annotationPart = null;
        $insideParens = false;

        foreach ($parts as $part) {
            // Track whether we're inside method args parentheses.
            // We don't allow annotations to start inside args.
            if ($insideParens) {
                $annotationParts[] = $part;
                if (str_contains($part, ')')) {
                    $insideParens = false;
                }

                continue;
            }

            if (str_contains($part, '(') && ! str_contains($part, ')')) {
                $insideParens = true;
            }

            $checkName = $this->extractAnnotationName($part);

            if ($this->isAnnotation($checkName)) {
                if (
                    $annotationPart != null ||
                    count($annotationParts) > 0
                ) {
                    if ($annotationPart != null) {
                        array_unshift($annotationParts, $annotationPart);
                    }

                    $tmpAnnotations[] = $annotationParts;

                    $annotationParts = [];
                }

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
            // Check if this is a prefix-based annotation that may be combined
            if ($this->matchesPrefix($tmpAnnotation[0]) !== null) {
                foreach ($this->parseCombinedPrefixAnnotations($tmpAnnotation) as $annotation) {
                    $finalAnnotations[] = $annotation;
                }

                continue;
            }

            $finalAnnotations[] = $tmpAnnotation;
        }

        return $this->convertAnnotations($finalAnnotations, $sourceLine);
    }

    /**
     * @param  list<string>  $annotation
     * @return list<list<string>>
     */
    protected function parseCombinedPrefixAnnotations(array $annotation): array
    {
        $results = [];
        $value = array_shift($annotation);
        $prefixes = $this->getPrefixesBySpecificity();

        $escapedPrefixes = array_map(fn ($p) => preg_quote($p, '/'), $prefixes);
        $prefixPattern = implode('|', $escapedPrefixes);

        $pattern = '/('.$prefixPattern.')([^'.preg_quote(implode('', $prefixes), '/').']+)/';

        preg_match_all($pattern, (string) $value, $matches, PREG_SET_ORDER);

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

        $parseResult->text = preg_replace(self::ANNOTATION_PATTERN, '', $text) ?? $text;

        preg_match_all(self::ANNOTATION_PATTERN, $text, $matches);

        foreach ($matches[1] as $match) {
            $parseResult->annotations = array_merge(
                $parseResult->annotations,
                $this->parseAnnotations($match, $sourceLine),
            );
        }

        return $parseResult;
    }

    /** @return list<ParsedAnnotation> */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }
}
