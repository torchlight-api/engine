<?php

namespace Torchlight\Engine\Annotations\Attributes;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class CssClassAnnotation extends AbstractAnnotation
{
    public static string $name = 'html-css-class';

    public function process(ParsedAnnotation $annotation): void
    {
        $className = ltrim($annotation->name, '.');

        $this->isCharacterRange()
            ? $this->addClassToCharacterRange($className)
            : $this->addLineClass($className);
    }
}
