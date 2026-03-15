<?php

namespace Torchlight\Engine\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Annotation
{
    /**
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $name,
        public array $aliases = [],
        public ?string $prefix = null,
        public bool $charRanges = false,
        public bool $lineRanges = true,
        public bool $methodArgs = true,
        public bool $options = true,
    ) {}
}
