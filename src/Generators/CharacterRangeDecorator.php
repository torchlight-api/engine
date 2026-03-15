<?php

namespace Torchlight\Engine\Generators;

class CharacterRangeDecorator
{
    /**
     * @param  list<array<string, int|string>>  $ranges
     */
    public function decorateCharacterRanges(string $html, array $ranges): string
    {
        $tokens = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) {
            return $html;
        }

        $output = [];

        for ($i = 0; $i < count($tokens); $i++) {
            if (str_starts_with($tokens[$i], '<')) {
                continue;
            }

            $tokens[$i] = html_entity_decode($tokens[$i]);
        }

        $tagStack = [];
        $visCount = 0;
        $foundAnyRanges = false;

        foreach ($ranges as $range) {
            if ($range['start'] === 0) {
                $attributes = ThemeStyleResolver::toAttributeString($this->stringAttributes($range));
                $output[] = "<span {$attributes}>";
                $foundAnyRanges = true;
            }
        }

        foreach ($tokens as $token) {
            if (str_starts_with($token, '<span')) {
                $tagStack[] = $token;

                $output[] = $token;

                continue;
            } elseif (str_starts_with($token, '</span')) {
                array_pop($tagStack);
                $output[] = $token;

                continue;
            }

            $chars = mb_str_split($token);

            foreach ($chars as $char) {
                $output[] = htmlentities($char);
                $visCount += 1;

                $rangesStarting = [];
                $rangesEnding = [];

                foreach ($ranges as $range) {
                    if ($range['start'] === $visCount) {
                        $rangesStarting[] = $range;
                        $foundAnyRanges = true;
                    } elseif ($range['end'] === $visCount) {
                        $rangesEnding[] = $range;
                        $foundAnyRanges = true;
                    }
                }

                if (count($rangesStarting) > 0) {
                    usort($rangesStarting, fn ($a, $b) => $b['end'] <=> $a['end']);

                    foreach ($tagStack as $item) {
                        $output[] = '</span>';
                    }

                    foreach ($rangesStarting as $range) {
                        $attributes = ThemeStyleResolver::toAttributeString($this->stringAttributes($range));
                        $output[] = "<span {$attributes}>";
                    }

                    foreach ($tagStack as $item) {
                        $output[] = $item;
                    }
                }

                if (count($rangesEnding) > 0) {
                    foreach ($tagStack as $item) {
                        $output[] = '</span>';
                    }

                    $output[] = str_repeat('</span>', count($rangesEnding));

                    foreach ($tagStack as $item) {
                        $output[] = $item;
                    }
                }
            }
        }

        if (! $foundAnyRanges) {
            return $html;
        }

        $result = implode('', $output);

        // Remove empty spans produced when range boundaries align with token boundaries.
        // The close-reopen cycle for the tag stack can leave behind <span ...></span>
        // with no content when the boundary falls exactly at a token edge.
        return preg_replace('/<span[^>]*><\/span>/', '', $result) ?? $result;
    }

    /**
     * @param  array<string, int|string>  $range
     * @return array<string, string>
     */
    private function stringAttributes(array $range): array
    {
        $attributes = [];

        foreach ($range as $name => $value) {
            if ($name === 'start' || $name === 'end' || ! is_string($value)) {
                continue;
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }
}
