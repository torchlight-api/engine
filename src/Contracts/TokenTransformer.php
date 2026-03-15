<?php

namespace Torchlight\Engine\Contracts;

use Torchlight\Engine\Generators\RenderableToken;
use Torchlight\Engine\Generators\RenderContext;

interface TokenTransformer
{
    /**
     * @param  array<int, array<int, RenderableToken>>  $tokens
     * @return array<int, array<int, RenderableToken>>
     */
    public function transform(RenderContext $context, array $tokens): array;

    /**
     * @param  string  $grammarName  The grammar name being rendered
     */
    public function supports(string $grammarName): bool;
}
