<?php

namespace Torchlight\Engine\Generators\Gutters;

use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Generators\RenderableToken;

class CollapseGutter extends AbstractGutter
{
    protected string $cssClass = 'summary-caret';

    /** @var array<int, string> */
    protected array $lineMarkers = [];

    public function reset(): void
    {
        $this->lineMarkers = [];
    }

    protected function getStartIndicator(string $styles): string
    {
        return $this->renderGutterSpan('', extraClasses: ['summary-caret-start'], colorStyles: $styles);
    }

    protected function getMiddleIndicator(string $styles): string
    {
        return $this->renderGutterSpan('', extraClasses: ['summary-caret-middle'], colorStyles: $styles);
    }

    protected function getEndIndicator(string $styles): string
    {
        return $this->renderGutterSpan('', extraClasses: ['summary-caret-end'], colorStyles: $styles);
    }

    protected function getEmptyIndicator(string $styles): string
    {
        return $this->renderGutterSpan('', extraClasses: ['summary-caret-empty'], colorStyles: $styles);
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

    public function renderSpacer(): string
    {
        if (empty($this->lineMarkers) || ! $this->options->showSummaryCarets) {
            return '';
        }

        return $this->getEmptyIndicator($this->getLineNumberColorStyles());
    }

    /**
     * @param  array<int, RenderableToken>  $tokens
     */
    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (empty($this->lineMarkers) || ! $this->options->showSummaryCarets) {
            return '';
        }

        $marker = $this->lineMarkers[$index] ?? null;

        return is_string($marker)
            ? $marker
            : $this->getEmptyIndicator($this->getLineNumberColorStyles());
    }
}
