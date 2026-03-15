<?php

namespace Torchlight\Engine\Annotations\Attributes;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Annotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'html-id', prefix: '#', charRanges: true)]
class IdAnnotation extends AbstractAnnotation
{
    public function process(ParsedAnnotation $annotation): void
    {
        $id = ltrim($annotation->name, '#');

        $this->isCharacterRange()
            ? $this->addIdToCharacterRange($id)
            : $this->addLineAttribute('id', $id);
    }
}
