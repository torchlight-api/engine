<?php

namespace Torchlight\Engine\Generators\TokenTransformers;

use Torchlight\Engine\Contracts\TokenTransformer;
use Torchlight\Engine\Generators\RenderableToken;
use Torchlight\Engine\Generators\RenderContext;
use Torchlight\Engine\Generators\TokenMetadata;

class IndentGuideTransformer implements TokenTransformer
{
    protected string $classPrefix = 'tl-guide';

    public function setClassPrefix(string $prefix): static
    {
        $this->classPrefix = $prefix;

        return $this;
    }

    public function getClassPrefix(): string
    {
        return $this->classPrefix;
    }

    public function supports(string $grammarName): bool
    {
        return $grammarName !== 'files';
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $tokens
     * @return array<int, array<int, RenderableToken>>
     */
    public function transform(RenderContext $context, array $tokens): array
    {
        $mode = $context->options->indentGuides;

        if ($mode === false) {
            return $tokens;
        }

        $tabWidth = $context->options->indentGuidesTabWidth ?? $this->detectTabWidth($tokens);

        if ($tabWidth < 1) {
            $tabWidth = 4;
        }

        $indents = $this->analyzeIndentation($tokens, $tabWidth);
        $indents = $this->fillBlankLines($indents);

        return $this->injectGuides($tokens, $indents, $tabWidth, $mode);
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $tokens
     * @return array<int, array{columns: int, levels: int, isEmpty: bool}>
     */
    protected function analyzeIndentation(array $tokens, int $tabWidth): array
    {
        $indents = [];

        foreach ($tokens as $lineIndex => $lineTokens) {
            $leadingColumns = 0;
            $isEmpty = true;

            foreach ($lineTokens as $token) {
                $text = $token->highlighted->token->text;

                if (trim((string) $text) === '') {
                    $leadingColumns += $this->countColumns($text, $tabWidth);

                    continue;
                }

                $isEmpty = false;
                break;
            }

            $indents[$lineIndex] = [
                'columns' => $leadingColumns,
                'levels' => intdiv($leadingColumns, $tabWidth),
                'isEmpty' => $isEmpty,
            ];
        }

        return $indents;
    }

    protected function countColumns(string $text, int $tabWidth): int
    {
        $columns = 0;

        for ($i = 0; $i < strlen($text); $i++) {
            if ($text[$i] === "\t") {
                $columns += $tabWidth - ($columns % $tabWidth);
            } else {
                $columns++;
            }
        }

        return $columns;
    }

    /** @param array<int, array<int, RenderableToken>> $tokens */
    protected function detectTabWidth(array $tokens): int
    {
        $indentSizes = [];

        foreach ($tokens as $lineTokens) {
            if (empty($lineTokens)) {
                continue;
            }

            $text = $lineTokens[0]->highlighted->token->text;

            if ($text === '' || trim((string) $text) !== '') {
                continue;
            }

            $size = strlen(str_replace("\t", '    ', $text));

            if ($size > 0) {
                $indentSizes[] = $size;
            }
        }

        return self::computeTabWidth($indentSizes);
    }

    /** @param list<int> $indentSizes */
    public static function computeTabWidth(array $indentSizes): int
    {
        if (empty($indentSizes)) {
            return 4;
        }

        $gcd = $indentSizes[0];

        foreach ($indentSizes as $size) {
            $gcd = self::gcd($gcd, $size);
        }

        return max(1, min(8, $gcd));
    }

    protected static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }

    /**
     * @param  array<int, array{columns: int, levels: int, isEmpty: bool}>  $indents
     * @return array<int, array{columns: int, levels: int, isEmpty: bool}>
     */
    protected function fillBlankLines(array $indents): array
    {
        $lineCount = count($indents);

        for ($i = 0; $i < $lineCount; $i++) {
            if (! $indents[$i]['isEmpty']) {
                continue;
            }

            $prevLevel = $this->findNearestNonEmptyLevel($indents, $i, -1);
            $nextLevel = $this->findNearestNonEmptyLevel($indents, $i, 1);

            $indents[$i]['levels'] = min($prevLevel, $nextLevel);
        }

        return $indents;
    }

    /**
     * @param  array<int, array{columns: int, levels: int, isEmpty: bool}>  $indents
     */
    protected function findNearestNonEmptyLevel(array $indents, int $from, int $direction): int
    {
        $i = $from + $direction;

        while (isset($indents[$i])) {
            if (! $indents[$i]['isEmpty']) {
                return $indents[$i]['levels'];
            }

            $i += $direction;
        }

        return 0;
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $tokens
     * @param  array<int, array{columns: int, levels: int, isEmpty: bool}>  $indents
     * @return array<int, array<int, RenderableToken>>
     */
    protected function injectGuides(array $tokens, array $indents, int $tabWidth, string $mode): array
    {
        foreach ($tokens as $lineIndex => $lineTokens) {
            $levels = $indents[$lineIndex]['levels'];

            if ($levels <= 0) {
                continue;
            }

            foreach ($lineTokens as $tokenIndex => $token) {
                $text = $token->highlighted->token->text;

                if (trim((string) $text) !== '') {
                    break;
                }

                $rawContent = self::renderGuideSpans($levels, $tabWidth, strlen((string) $text), $mode, $this->classPrefix);

                $token->highlighted->token->text = $rawContent;

                $tokens[$lineIndex][$tokenIndex] = new RenderableToken(
                    $token->highlighted,
                    (new TokenMetadata)->setRawContent($rawContent),
                );

                break;
            }
        }

        return $tokens;
    }

    public static function renderGuideSpans(
        int $levels,
        int $tabWidth,
        int $totalChars,
        string $mode,
        string $classPrefix = 'tl-guide',
    ): string {
        $html = '';
        $charsUsed = 0;

        for ($depth = 0; $depth < $levels; $depth++) {
            $charsForLevel = min($tabWidth, $totalChars - $charsUsed);

            if ($charsForLevel <= 0) {
                break;
            }

            $depthNum = $depth + 1;

            if ($mode === 'ascii') {
                $content = '│'.str_repeat(' ', max(0, $charsForLevel - 1));
            } else {
                $content = str_repeat(' ', $charsForLevel);
            }

            $html .= sprintf(
                '<span class="%s %s-d%d">%s</span>',
                $classPrefix,
                $classPrefix,
                $depthNum,
                $content
            );

            $charsUsed += $charsForLevel;
        }

        if ($charsUsed < $totalChars) {
            $html .= str_repeat(' ', $totalChars - $charsUsed);
        }

        return $html;
    }
}
