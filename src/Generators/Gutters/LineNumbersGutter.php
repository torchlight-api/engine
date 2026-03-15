<?php

namespace Torchlight\Engine\Generators\Gutters;

use Phiki\Theme\TokenSettings;
use Torchlight\Engine\Generators\RenderableToken;
use Torchlight\Engine\Options;

class LineNumbersGutter extends AbstractGutter
{
    protected string $cssClass = 'line-number';

    /** @var array<int, list<string>> */
    protected array $lineNumberScopes = [];

    /** @var array<int, array{0:string, 1:list<string>}> */
    protected array $lineMarkerReplacements = [];

    protected int $maxLineCount = 0;

    /** @var array<int, int|null> */
    protected array $reindexedLines = [];

    /** @var array<int, int> */
    protected array $forcedDisplayLine = [];

    protected int $startLineOffset = 0;

    /** @var array<int, true> */
    protected array $highlightedLines = [];

    public function reset(): void
    {
        $this->forcedDisplayLine = [];
        $this->lineNumberScopes = [];
        $this->highlightedLines = [];
        $this->lineMarkerReplacements = [];
        $this->maxLineCount = 0;
        $this->reindexedLines = [];
        $this->startLineOffset = 0;
    }

    /** @param list<string> $scopes */
    public function setLineScopes(int $line, array $scopes): static
    {
        $this->lineNumberScopes[$line] = array_values($scopes);

        return $this;
    }

    public function markLineHighlighted(int $line): static
    {
        $this->highlightedLines[$line] = true;

        return $this;
    }

    public function getMaxLineCount(): int
    {
        return $this->maxLineCount;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->options = $options;
        $this->startLineOffset = $options->lineNumbersStart - 1;

        return $this;
    }

    public function setMaxLineCount(int $maxLineCount): static
    {
        $this->maxLineCount = $maxLineCount;

        return $this;
    }

    public function setStartLineOffset(int $offset): static
    {
        $this->startLineOffset = $offset;

        return $this;
    }

    public function forceLineDisplay(int $originalLine, int $display): static
    {
        $this->forcedDisplayLine[$originalLine] = $display;

        return $this;
    }

    public function reindexLine(int $originalLine, ?int $newLine, ?int $relativeOffset = null): static
    {
        if ($newLine !== null) {
            $this->maxLineCount = max($this->maxLineCount, $newLine);
        }

        if (array_key_exists($originalLine, $this->reindexedLines) && $relativeOffset != null) {
            $newLine = $this->reindexedLines[$originalLine] + $relativeOffset;
        }

        $this->reindexedLines[$originalLine] = $newLine;

        if ($originalLine < $this->maxLineCount) {
            $adjust = 1;
            for ($i = $originalLine + 1; $i < $this->maxLineCount; $i++) {
                $this->reindexedLines[$i] = $newLine + $adjust;

                $adjust++;
            }
        }

        return $this;
    }

    protected function isHighlighted(int $line): bool
    {
        return array_key_exists($line, $this->highlightedLines);
    }

    /**
     * @param  array<string, TokenSettings>  $settings
     * @return array<string, TokenSettings>
     */
    private function removeBackground(array $settings): array
    {
        $newSettings = [];

        foreach ($settings as $id => $setting) {
            $newSettings[$id] = new TokenSettings(
                background: null,
                foreground: $setting->foreground,
                fontStyle: $setting->fontStyle,
            );
        }

        return $newSettings;
    }

    /**
     * @param  array<int, RenderableToken>  $tokens
     */
    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $colorStyles = $this->resolveColorStyles($relativeLine);

        $resetLine = false;

        if (array_key_exists($index, $this->reindexedLines)) {
            $newOffset = $this->reindexedLines[$index];

            if ($newOffset === null) {
                $resetLine = true;
            } else {
                $this->startLineOffset = $newOffset - 1 - $index;
            }
        }

        $displayLine = $this->startLineOffset + $relativeLine;

        if (array_key_exists($index, $this->forcedDisplayLine)) {
            $displayLine = $this->forcedDisplayLine[$index];
        }

        $lineNumberText = (string) $displayLine;
        $textLen = mb_strlen($lineNumberText);

        if (! $this->options->lineNumbersEnabled || $resetLine) {
            $lineNumberText = count($this->lineMarkerReplacements) > 0 ? ' ' : '';
        }

        if (array_key_exists($index, $this->lineMarkerReplacements)) {
            $marker = $this->lineMarkerReplacements[$index];
            $lineNumberText = $marker[0];
            $textLen = mb_strlen($lineNumberText);

            if (! empty($marker[1])) {
                $colorStyles = implode('', $this->getTokenStyles($this->makeToken($lineNumberText, $marker[1])));
            }
        }

        return $this->buildLineNumberSpan($colorStyles, $lineNumberText, $textLen);
    }

    public function renderSpacer(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $content = str_repeat(' ', mb_strlen(strval($this->maxLineCount)));

        return $this->buildLineNumberSpan($this->getLineNumberColorStyles(), $content);
    }

    private function isEnabled(): bool
    {
        return $this->options->lineNumbersEnabled || count($this->lineMarkerReplacements) > 0;
    }

    private function resolveColorStyles(int $relativeLine): string
    {
        $colorStyles = $this->isHighlighted($relativeLine)
            ? $this->getThemeValueStylesString('color', ['torchlight.activeLineNumberColor', 'editorLineNumber.foreground'])
            : $this->getLineNumberColorStyles();

        if (array_key_exists($relativeLine, $this->lineNumberScopes)) {
            $settings = $this->removeBackground(
                $this->getScopeSettings($this->lineNumberScopes[$relativeLine])
            );

            $colorStyles = $this->services()->getSettingsStyleString($settings);
        }

        return $colorStyles;
    }

    private function buildLineNumberSpan(string $colorStyles, string $text, ?int $textLen = null): string
    {
        $textLen ??= mb_strlen($text);
        $longestLineNumberLen = mb_strlen(strval($this->maxLineCount));

        $lineNumberStyles = '';

        if ($this->options->lineNumbersStyle != '') {
            $lineNumberStyles .= $this->options->lineNumbersStyle;
        }

        if (mb_strlen(trim($colorStyles)) > 0 && ! str_ends_with($colorStyles, ';')) {
            $colorStyles .= '; ';
        }

        $styles = $colorStyles.$lineNumberStyles;

        if ($this->options->lineNumberAndDiffIndicatorRightPadding > 0) {
            if (! $this->generationOptions?->hasSeparatePaddingGutter) {
                $text .= str_repeat(' ', $this->options->lineNumberAndDiffIndicatorRightPadding);
            }
        }

        $leadingPadding = '';

        if ($textLen < $longestLineNumberLen) {
            $leadingPadding = str_repeat(' ', $longestLineNumberLen - $textLen);
        }

        return '<span style="'.$styles.'" class="line-number">'.$leadingPadding.$text.'</span>';
    }

    /** @param list<int> $removedLineNumbers */
    public function adjustForRemovedLines(array $removedLineNumbers, int $originalLineCount): void
    {
        if (empty($removedLineNumbers)) {
            return;
        }

        sort($removedLineNumbers);
        $offset = 0;

        for ($line = 1; $line <= $originalLineCount; $line++) {
            $index = $line - 1;

            if (in_array($line, $removedLineNumbers, true)) {
                $offset++;

                continue;
            }

            // Only adjust lines that haven't been explicitly reindexed
            if ($offset > 0 && ! array_key_exists($index, $this->reindexedLines)) {
                $this->reindexedLines[$index] = $line - $offset;
            }
        }

        $this->maxLineCount = $originalLineCount - count($removedLineNumbers);
    }

    /**
     * @param  array{0:string, 1:list<string>}  $marker
     */
    public function replaceLineMarker(int $line, array $marker): static
    {
        $this->lineMarkerReplacements[$line - 1] = $marker;

        return $this;
    }
}
