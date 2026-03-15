<?php

namespace Torchlight\Engine\Generators\Gutters;

use Torchlight\Engine\Annotations\Diff\DiffAddAnnotation;
use Torchlight\Engine\Annotations\Diff\DiffRemoveAnnotation;
use Torchlight\Engine\Generators\RenderableToken;

class DiffGutter extends AbstractGutter
{
    protected string $cssClass = 'diff-indicator';

    /** @var array<int, string> */
    protected array $lineMarkers = [];

    public function reset(): void
    {
        $this->lineMarkers = [];
    }

    public function hasMarkers(): bool
    {
        return count($this->lineMarkers) > 0;
    }

    private function shouldRenderPadding(): bool
    {
        return
            $this->options->lineNumberAndDiffIndicatorRightPadding > 0 &&
            $this->options->diffIndicatorsInPlaceOfNumbers == false;
    }

    /**
     * @param  array<int, RenderableToken>  $tokens
     */
    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (count($this->lineMarkers) === 0) {
            return '';
        }

        if (! array_key_exists($index, $this->lineMarkers)) {
            return $this->renderEmptyIndicator();
        }

        $marker = $this->lineMarkers[$index];
        $scopes = $this->options->diffPreserveSyntaxColors ? '' : DiffRemoveAnnotation::DIFF_REMOVE_SCOPES;
        $className = 'diff-indicator-remove';

        if ($marker == '+') {
            $scopes = $this->options->diffPreserveSyntaxColors ? '' : DiffAddAnnotation::DIFF_ADD_SCOPES;
            $className = 'diff-indicator-add';
        }

        if ($this->shouldRenderPadding()) {
            $marker .= str_repeat(' ', $this->options->lineNumberAndDiffIndicatorRightPadding);
        }

        if (! is_array($scopes)) {
            $scopes = [$scopes];
        }

        return $this->renderText($marker, $scopes, ['diff-indicator', $className], ['user-select' => 'none']);
    }

    public function renderSpacer(): string
    {
        if (count($this->lineMarkers) === 0) {
            return '';
        }

        return $this->renderEmptyIndicator();
    }

    private function renderEmptyIndicator(): string
    {
        $contentLen = 1;

        if ($this->shouldRenderPadding()) {
            $contentLen += $this->options->lineNumberAndDiffIndicatorRightPadding;
        }

        return $this->renderGutterSpan(
            str_repeat(' ', $contentLen),
            class: 'diff-indicator diff-indicator-empty',
            colorStyles: $this->getThemeValueStylesString('color', 'editorLineNumber.foreground'),
        );
    }

    public function setLineMarker(int $line, string $marker): static
    {
        $this->lineMarkers[$line - 1] = $marker;

        return $this;
    }
}
