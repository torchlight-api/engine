<?php

namespace Torchlight\Engine\Pipeline;

use Phiki\Grammar\Grammar;
use Phiki\Grammar\ParsedGrammar;
use Phiki\Token\Token;

class ProcessedTokens
{
    public function __construct(
        /**
         * @var array<int, Token[]>
         */
        public readonly array $tokens,

        public readonly string $cleanedText,

        public readonly Grammar|ParsedGrammar|null $grammar,

        public readonly string $languageName,

        public readonly string $scopeName,
    ) {}
}
