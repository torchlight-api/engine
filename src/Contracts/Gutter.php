<?php

namespace Torchlight\Engine\Contracts;

use Torchlight\Engine\Generators\GenerationOptions;
use Torchlight\Engine\Generators\RenderableToken;

interface Gutter
{
    /**
     * @param  array<int, RenderableToken>  $tokens
     */
    public function renderLine(int $relativeLine, int $index, array $tokens): string;

    public function renderSpacer(): string;

    public function shouldRender(): bool;

    public function reset(): void;

    public function getPriority(): int;

    public function decorateLine(int $relativeLine, int $index, GenerationOptions $options): void;
}
