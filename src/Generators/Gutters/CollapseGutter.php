<?php

namespace Torchlight\Engine\Generators\Gutters;

use Torchlight\Engine\Annotations\Ranges\ImpactedRange;

class CollapseGutter extends AbstractGutter
{
    protected array $lineMarkers = [];

    public function reset(): void
    {
        $this->lineMarkers = [];
    }

    protected function getStartIndicator(string $styles): string
    {
        return '<span class="summary-caret summary-caret-start" style="user-select: none;'.$styles.'"></span>';
    }

    protected function getMiddleIndicator(string $styles): string
    {
        return '<span class="summary-caret summary-caret-middle" style="user-select: none;'.$styles.'"></span>';
    }

    protected function getEndIndicator(string $styles): string
    {
        return '<span class="summary-caret summary-caret-end" style="user-select: none;'.$styles.'"></span>';
    }

    protected function getEmptyIndicator(string $styles): string
    {
        return '<span class="summary-caret summary-caret-empty" style="user-select: none;'.$styles.'"></span>';
    }

    public function markRange(ImpactedRange $range): static
    {
        if (! $this->options->showSummaryCarets) {
            return $this;
        }

        if ($range->isSingleLine) {
            return $this;
        }

        $styles = $this->getLineNumberColorStyles();

        $this->lineMarkers[$range->startLine - 1] = $this->getStartIndicator($styles);
        $this->lineMarkers[$range->endLine - 1] = $this->getEndIndicator($styles);

        for ($i = $range->startLine + 1; $i < $range->endLine; $i++) {
            $this->lineMarkers[$i - 1] = $this->getMiddleIndicator($styles);
        }

        return $this;
    }

    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (empty($this->lineMarkers) || ! $this->options->showSummaryCarets) {
            return '';
        }

        return $this->lineMarkers[$index] ?? $this->getEmptyIndicator($this->getLineNumberColorStyles());
    }
}
