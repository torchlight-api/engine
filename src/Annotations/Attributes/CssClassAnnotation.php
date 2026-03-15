<?php

namespace Torchlight\Engine\Annotations\Attributes;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Annotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'html-css-class', prefix: '.', charRanges: true)]
class CssClassAnnotation extends AbstractAnnotation
{
    public function process(ParsedAnnotation $annotation): void
    {
        $className = ltrim($annotation->name, '.');

        $this->isCharacterRange()
            ? $this->addClassToCharacterRange($className)
            : $this->addLineClass($className);
    }
}
