<?php

namespace Torchlight\Engine\CommonMark;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;

interface BlockCache
{
    public function has(FencedCode $node): bool;

    public function get(FencedCode $node): string;

    public function set(FencedCode $node, string $result): void;
}
