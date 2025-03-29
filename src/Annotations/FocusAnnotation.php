<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class FocusAnnotation extends AbstractAnnotation
{
    public static string $name = 'focus';

    public static array $aliases = ['**'];

    public function process(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-focus-lines')
            ->addLineClass('line-focus');
    }
}
