<?php

namespace Torchlight\Engine\Generators\Gutters;

use Torchlight\Engine\Annotations\Diff\DiffAddAnnotation;
use Torchlight\Engine\Annotations\Diff\DiffRemoveAnnotation;

class DiffGutter extends AbstractGutter
{
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

    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (count($this->lineMarkers) === 0) {
            return '';
        }

        if (! array_key_exists($index, $this->lineMarkers)) {
            $styles = $this->getThemeValueStylesString('color', 'editorLineNumber.foreground');
            $contentLen = 1;

            if ($this->shouldRenderPadding()) {
                $contentLen += $this->options->lineNumberAndDiffIndicatorRightPadding;
            }

            return '<span class="diff-indicator diff-indicator-empty" style="user-select: none;'.$styles.'">'.str_repeat(' ', $contentLen).'</span>';
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

    public function setLineMarker(int $line, string $marker): static
    {
        $this->lineMarkers[$line - 1] = $marker;

        return $this;
    }
}
