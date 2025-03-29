<?php

namespace Torchlight\Engine\Preprocessors;

use Phiki\Grammar\Grammar;

class PreprocessorArgs
{
    public function __construct(
        public array $tokens,
        public string $originalText,
        public string|Grammar $grammar,
        public ?string $languageName
    ) {}
}
