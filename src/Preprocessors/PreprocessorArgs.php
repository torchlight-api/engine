<?php

namespace Torchlight\Engine\Preprocessors;

use Phiki\Grammar\Grammar;
use Phiki\Grammar\ParsedGrammar;
use Phiki\Token\Token;

class PreprocessorArgs
{
    /**
     * @param  array<int, array<int, Token>>  $tokens
     */
    public function __construct(
        public array $tokens,
        public string $originalText,
        public string|Grammar|ParsedGrammar $grammar,
        public ?string $languageName
    ) {}
}
