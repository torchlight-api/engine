<?php

namespace Torchlight\Engine\Annotations\Ranges;

readonly class ImpactedRange
{
    public function __construct(
        public bool $isSingleLine,
        public int $startLine,
        public int $endLine
    ) {}
}
