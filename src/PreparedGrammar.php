<?php

namespace Torchlight\Engine;

use Phiki\Grammar\Grammar;
use Phiki\Grammar\ParsedGrammar;

readonly class PreparedGrammar
{
    public function __construct(
        public Grammar|ParsedGrammar|string $grammar,
        public string $vanityLabel = '',
    ) {}

    public function getName(): string
    {
        if (is_string($this->grammar)) {
            return $this->grammar;
        }

        return $this->grammar->name ?? '';
    }
}
