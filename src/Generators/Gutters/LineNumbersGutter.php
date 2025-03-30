<?php

namespace Torchlight\Engine\Generators\Gutters;

use Phiki\Theme\TokenSettings;
use Torchlight\Engine\Options;

class LineNumbersGutter extends AbstractGutter
{
    protected array $lineNumberScopes = [];

    protected array $lineMarkerReplacements = [];

    protected int $maxLineCount = 0;

    protected array $reindexedLines = [];

    protected int $startLineOffset = 0;

    protected array $highlightedLines = [];

    public function reset(): void
    {
        $this->lineNumberScopes = [];
        $this->highlightedLines = [];
        $this->lineMarkerReplacements = [];
        $this->maxLineCount = 0;
        $this->reindexedLines = [];
        $this->startLineOffset = 0;
    }

    public function setLineScopes(int $line, array $scopes): static
    {
        $this->lineNumberScopes[$line] = $scopes;

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
     * @param  TokenSettings[]  $settings
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

    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (! $this->options->lineNumbersEnabled && count($this->lineMarkerReplacements) === 0) {
            return '';
        }

        $longestLineNumberLen = mb_strlen(strval($this->maxLineCount));

        $colorStyles = $this->isHighlighted($relativeLine)
            ? $this->htmlGenerator->getThemeValueStylesString('color', ['torchlight.activeLineNumberColor', 'editorLineNumber.foreground'])
            : $this->getLineNumberColorStyles();

        if (array_key_exists($relativeLine, $this->lineNumberScopes)) {
            $settings = $this->removeBackground(
                $this->getScopeSettings($this->lineNumberScopes[$relativeLine])
            );

            $colorStyles = $this->htmlGenerator->getSettingsStyleString($settings);
        }

        $lineNumberStyles = '';

        if ($this->options->lineNumbersStyle != '') {
            $lineNumberStyles .= $this->options->lineNumbersStyle;
        }

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

        $lineNumberText = $displayLine;
        $textLen = mb_strlen($lineNumberText);

        if (! $this->options->lineNumbersEnabled || $resetLine) {
            if (count($this->lineMarkerReplacements) > 0) {
                $lineNumberText = ' ';
            } else {
                $lineNumberText = '';
            }
        }

        if (array_key_exists($index, $this->lineMarkerReplacements)) {
            $marker = $this->lineMarkerReplacements[$index];
            $lineNumberText = $marker[0];
            $textLen = mb_strlen($lineNumberText);

            if (! empty($marker[1])) {
                // Apply styles from specified scopes.
                $colorStyles = implode('', $this->htmlGenerator->getTokenStyles($this->makeToken($lineNumberText, $marker[1])));
            }
        }

        if (mb_strlen(trim($colorStyles)) > 0 && ! str_ends_with($colorStyles, ';')) {
            $colorStyles .= '; ';
        }

        $lineNumberStyles = $colorStyles.$lineNumberStyles;

        if ($this->options->lineNumberAndDiffIndicatorRightPadding > 0) {
            $shouldAdd = true;

            // If the user wants to render diff indicators separately,
            // we will skip adding the padding here, and take care
            // of it inside the diff gutter to avoid pushing +/-
            if ($this->options->diffIndicatorsInPlaceOfNumbers === false && $this->engine->diffGutter()->hasMarkers()) {
                $shouldAdd = false;
            }

            if ($shouldAdd) {
                $lineNumberText .= str_repeat(' ', $this->options->lineNumberAndDiffIndicatorRightPadding);
            }
        }

        $leadingPadding = '';

        if ($textLen < $longestLineNumberLen) {
            $leadingPadding = str_repeat(' ', $longestLineNumberLen - $textLen);
        }

        return implode('', [
            '<span style="'.$lineNumberStyles.'" class="line-number">',
            $leadingPadding.$lineNumberText,
            '</span>',
        ]);
    }

    public function replaceLineMarker(int $line, array $marker): static
    {
        $this->lineMarkerReplacements[$line - 1] = $marker;

        return $this;
    }
}
