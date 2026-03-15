<?php

namespace Torchlight\Engine\Contracts;

use Phiki\Token\Token;
use Torchlight\Engine\Engine;
use Torchlight\Engine\Preprocessors\PreprocessorArgs;

interface Preprocessor
{
    /**
     * @param  PreprocessorArgs  $args  Arguments containing tokens, code, grammar, etc.
     * @param  Engine  $engine  The engine instance
     * @return array<int, array<int, Token>> Modified tokens
     */
    public function process(PreprocessorArgs $args, Engine $engine): array;

    public function supports(?string $grammarName): bool;
}
