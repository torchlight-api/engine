<?php

namespace Torchlight\Engine\Annotations\Diff;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Annotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'word-diff', aliases: ['wd'])]
class WordDiffAnnotation extends AbstractAnnotation
{
    /** @var int[] */
    protected array $wdLines = [];

    public function process(ParsedAnnotation $annotation): void
    {
        $this->wdLines[] = $this->activeRange()->startLine;
    }

    public function afterProcess(): void
    {
        foreach ($this->wdLines as $newLine) {
            $oldLine = $newLine - 1;

            if ($oldLine >= 1) {
                $this->processWordDiffPair($oldLine, $newLine);
            }
        }
    }

    protected function processWordDiffPair(int $oldLine, int $newLine): void
    {
        $oldText = $this->annotationEngine->getLineText($oldLine);
        $newText = $this->annotationEngine->getLineText($newLine);

        if ($oldText === null || $newText === null) {
            return;
        }

        $oldText = rtrim($oldText);
        $newText = rtrim($newText);

        if ($oldText === $newText) {
            return;
        }

        $regions = $this->computeMultiRegionDiff($oldText, $newText);

        if (empty($regions)) {
            return;
        }

        $this->addBlockClass('has-word-diff');

        $this->annotationEngine->removeLine($newLine);

        $this->annotationEngine->addLineClass($oldLine, 'line-word-diff');

        $delStyle = $this->getThemeStyle('line-remove');
        $insStyle = $this->getThemeStyle('line-add');

        $this->annotationEngine->modifyLineContents(
            $oldLine,
            fn (string $html) => $this->injectInlineDiff($html, $regions, $delStyle, $insStyle)
        );
    }

    /**
     * @param  array<int, array{oldStart: int, oldEnd: int, newText: string}>  $regions
     */
    protected function injectInlineDiff(
        string $html,
        array $regions,
        string $delStyle = '',
        string $insStyle = '',
    ): string {
        $tokens = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) {
            return $html;
        }

        // Decode HTML entities in text nodes for accurate character counting.
        for ($i = 0; $i < count($tokens); $i++) {
            if (! str_starts_with($tokens[$i], '<')) {
                $tokens[$i] = html_entity_decode($tokens[$i]);
            }
        }

        $delStyleAttr = $delStyle !== '' ? " style=\"{$delStyle}\"" : '';
        $insStyleAttr = $insStyle !== '' ? " style=\"{$insStyle}\"" : '';
        $delOpen = '<span class="word-diff-del"'.$delStyleAttr.'>';
        $insOpen = '<span class="word-diff-ins"'.$insStyleAttr.'>';

        $tagStack = [];
        $visCount = 0;
        $output = [];
        $regionIndex = 0;
        $inDel = false;

        // Handle pure insertions at position 0, before any visible characters.
        while ($regionIndex < count($regions)) {
            $region = $regions[$regionIndex];

            if ($region['oldEnd'] < $region['oldStart'] && $region['oldStart'] <= 1) {
                $output[] = $insOpen.htmlspecialchars((string) $region['newText']).'</span>';
                $regionIndex++;
            } else {
                break;
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
                $visCount++;

                if ($regionIndex < count($regions)) {
                    $region = $regions[$regionIndex];
                    $hasDel = $region['oldStart'] <= $region['oldEnd'];
                    $hasIns = $region['newText'] !== '';

                    // Check if a del range starts at this character.
                    if ($hasDel && ! $inDel && $visCount === $region['oldStart']) {
                        foreach ($tagStack as $item) {
                            $output[] = '</span>';
                        }
                        $output[] = $delOpen;
                        foreach ($tagStack as $item) {
                            $output[] = $item;
                        }
                        $inDel = true;
                    }

                    $output[] = htmlentities($char);

                    // Check if del range ends at this character.
                    if ($hasDel && $inDel && $visCount === $region['oldEnd']) {
                        foreach ($tagStack as $item) {
                            $output[] = '</span>';
                        }
                        $output[] = '</span>'; // Close word-diff-del.

                        if ($hasIns) {
                            $output[] = $insOpen.htmlspecialchars((string) $region['newText']).'</span>';
                        }

                        foreach ($tagStack as $item) {
                            $output[] = $item;
                        }

                        $inDel = false;
                        $regionIndex++;
                    }

                    // Handle pure insertion
                    if (! $hasDel && $hasIns && $visCount === ($region['oldStart'] - 1)) {
                        foreach ($tagStack as $item) {
                            $output[] = '</span>';
                        }
                        $output[] = $insOpen.htmlspecialchars((string) $region['newText']).'</span>';
                        foreach ($tagStack as $item) {
                            $output[] = $item;
                        }
                        $regionIndex++;
                    }
                } else {
                    $output[] = htmlentities($char);
                }
            }
        }

        $result = implode('', $output);

        // Remove empty spans produced when diff boundaries align with token boundaries.
        return preg_replace('/<span[^>]*><\/span>/', '', $result) ?? $result;
    }

    /**
     * @return list<string>
     */
    protected function tokenizeForDiff(string $text): array
    {
        preg_match_all('/\w+|\s+|[^\w\s]/u', $text, $matches);

        return $matches[0];
    }

    /**
     * @return array<int, array{oldStart: int, oldEnd: int, newText: string}>
     */
    protected function computeMultiRegionDiff(string $oldText, string $newText): array
    {
        $oldTokens = $this->tokenizeForDiff($oldText);
        $newTokens = $this->tokenizeForDiff($newText);
        $oldLen = count($oldTokens);
        $newLen = count($newTokens);

        $oldOffsets = [];
        $pos = 1;
        for ($i = 0; $i < $oldLen; $i++) {
            $oldOffsets[$i] = $pos;
            $pos += mb_strlen($oldTokens[$i]);
        }

        $newOffsets = [];
        $pos = 1;
        for ($i = 0; $i < $newLen; $i++) {
            $newOffsets[$i] = $pos;
            $pos += mb_strlen($newTokens[$i]);
        }

        // Build LCS DP table on tokens.
        /** @var array<int, array<int, int>> $dp */
        $dp = [];
        for ($i = 0; $i <= $oldLen; $i++) {
            for ($j = 0; $j <= $newLen; $j++) {
                if ($i === 0 || $j === 0) {
                    $dp[$i][$j] = 0;
                } elseif ($oldTokens[$i - 1] === $newTokens[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                } else {
                    $dp[$i][$j] = max($dp[$i - 1][$j] ?? 0, $dp[$i][$j - 1] ?? 0);
                }
            }
        }

        // Backtrack to produce token-level edit operations.
        /** @var list<array{type: 'equal'|'insert'|'delete', oldIdx: int|null, newIdx: int|null}> $ops */
        $ops = [];
        $i = $oldLen;
        $j = $newLen;

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $oldTokens[$i - 1] === $newTokens[$j - 1]) {
                $ops[] = ['type' => 'equal', 'oldIdx' => $i - 1, 'newIdx' => $j - 1];
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || (($dp[$i][$j - 1] ?? 0) >= ($dp[$i - 1][$j] ?? 0)))) {
                $ops[] = ['type' => 'insert', 'oldIdx' => null, 'newIdx' => $j - 1];
                $j--;
            } else {
                $ops[] = ['type' => 'delete', 'oldIdx' => $i - 1, 'newIdx' => null];
                $i--;
            }
        }

        $ops = array_reverse($ops);

        // Group consecutive non-equal ops into regions with character positions.
        $regions = [];
        $k = 0;
        $opCount = count($ops);

        while ($k < $opCount) {
            if ($ops[$k]['type'] === 'equal') {
                $k++;

                continue;
            }

            // Collect consecutive deletes and inserts.
            $deletedIndices = [];
            $insertedText = '';

            while ($k < $opCount && $ops[$k]['type'] !== 'equal') {
                if ($ops[$k]['type'] === 'delete') {
                    $oldIdx = $ops[$k]['oldIdx'];
                    if ($oldIdx !== null) {
                        $deletedIndices[] = $oldIdx;
                    }
                } elseif ($ops[$k]['type'] === 'insert') {
                    $newIdx = $ops[$k]['newIdx'];
                    if ($newIdx !== null) {
                        $insertedText .= $newTokens[$newIdx];
                    }
                }
                $k++;
            }

            if (! empty($deletedIndices)) {
                $firstIdx = min($deletedIndices);
                $lastIdx = max($deletedIndices);
                $oldStart = $oldOffsets[$firstIdx];
                $oldEnd = $oldOffsets[$lastIdx] + mb_strlen($oldTokens[$lastIdx]) - 1;

                $regions[] = [
                    'oldStart' => $oldStart,
                    'oldEnd' => $oldEnd,
                    'newText' => $insertedText,
                ];
            } elseif ($insertedText !== '') {
                // Pure insertion.
                $anchorPos = 0;
                for ($p = $k - 1; $p >= 0; $p--) {
                    if ($ops[$p]['type'] === 'equal' || $ops[$p]['type'] === 'delete') {
                        $idx = $ops[$p]['oldIdx'];
                        if ($idx !== null) {
                            $anchorPos = $oldOffsets[$idx] + mb_strlen($oldTokens[$idx]) - 1;
                            break;
                        }
                    }
                }

                $regions[] = [
                    'oldStart' => $anchorPos + 1,
                    'oldEnd' => $anchorPos,
                    'newText' => $insertedText,
                ];
            }
        }

        return $regions;
    }

    public function reset(): void
    {
        parent::reset();
        $this->wdLines = [];
    }
}
