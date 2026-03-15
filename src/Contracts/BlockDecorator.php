<?php

namespace Torchlight\Engine\Contracts;

use Torchlight\Engine\Generators\RenderContext;

interface BlockDecorator
{
    /**
     * @param  RenderContext  $context  Rendering context with options and themes
     */
    public function shouldRender(RenderContext $context): bool;

    /**
     * @param  RenderContext  $context  Rendering context with options and themes
     * @param  string  $cleanedText  Plain text content of the code block (no HTML)
     * @return string HTML to append to the code block
     */
    public function render(RenderContext $context, string $cleanedText): string;

    public function getPriority(): int;
}
