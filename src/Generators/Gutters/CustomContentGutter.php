<?php

namespace Torchlight\Engine\Generators\Gutters;

use Torchlight\Engine\Generators\RenderableToken;

class CustomContentGutter extends AbstractGutter
{
    protected int $priority = 300;

    protected string $cssClass = 'custom-gutter';

    /** @var array<int, string> */
    protected array $lineContent = [];

    public function reset(): void
    {
        $this->lineContent = [];
    }

    public function setLineContent(int $line, string $content): static
    {
        $this->lineContent[$line - 1] = $content;

        return $this;
    }

    public function hasContent(): bool
    {
        return count($this->lineContent) > 0;
    }

    public function shouldRender(): bool
    {
        return $this->hasContent();
    }

    /**
     * @param  array<int, RenderableToken>  $tokens
     */
    public function renderLine(int $relativeLine, int $index, array $tokens): string
    {
        if (! $this->hasContent()) {
            return '';
        }

        $content = $this->lineContent[$index] ?? '';
        $maxWidth = $this->getMaxContentWidth();
        $padded = $content.str_repeat(' ', max(0, $maxWidth - mb_strwidth($content)));

        $widthCss = "display:inline-block;width:{$maxWidth}ch;";

        return $this->renderGutterSpan(
            htmlspecialchars($padded),
            colorStyles: $widthCss.$this->getLineNumberColorStyles(),
        );
    }

    public function renderSpacer(): string
    {
        if (! $this->hasContent()) {
            return '';
        }

        $maxWidth = $this->getMaxContentWidth();
        $widthCss = "display:inline-block;width:{$maxWidth}ch;";

        return $this->renderGutterSpan(
            str_repeat(' ', $maxWidth),
            colorStyles: $widthCss.$this->getLineNumberColorStyles(),
        );
    }

    private function getMaxContentWidth(): int
    {
        if (empty($this->lineContent)) {
            return 0;
        }

        $widths = array_map(
            mb_strwidth(...),
            $this->lineContent
        );

        return max($widths);
    }
}
