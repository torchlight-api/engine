<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class MonoAnnotation extends AbstractAnnotation
{
    public static string $name = 'mono';

    public static array $aliases = [];

    public function process(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-mono-lines')
            ->addLineClass(['line-mono']);
    }
}
