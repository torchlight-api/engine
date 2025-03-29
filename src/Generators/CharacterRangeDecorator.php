<?php

namespace Torchlight\Engine\Generators;

use Torchlight\Engine\Generators\Concerns\ManagesStyles;

class CharacterRangeDecorator
{
    use ManagesStyles;

    public function decorateCharacterRanges(string $html, array $ranges): string
    {
        $tokens = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
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
                    usort($rangesStarting, function ($a, $b) {
                        return $b['end'] <=> $a['end'];
                    });

                    foreach ($tagStack as $item) {
                        $output[] = '</span>';
                    }

                    foreach ($rangesStarting as $range) {
                        $attributes = $this->toAttributeString(array_diff_key($range, array_flip(['start', 'end'])));
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

        return implode('', $output);
    }
}
