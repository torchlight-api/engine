<?php

namespace Torchlight\Engine\Contracts;

use Torchlight\Engine\Engine;
use Torchlight\Engine\Preprocessors\PreprocessorArgs;

interface Preprocessor
{
    public function process(PreprocessorArgs $args, Engine $engine): array;
}
