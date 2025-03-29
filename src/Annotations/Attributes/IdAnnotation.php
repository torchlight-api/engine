<?php

namespace Torchlight\Engine\Annotations\Attributes;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class IdAnnotation extends AbstractAnnotation
{
    public static string $name = 'html-id';

    public function process(ParsedAnnotation $annotation): void
    {
        $id = ltrim($annotation->name, '#');

        $this->isCharacterRange()
            ? $this->addIdToCharacterRange($id)
            : $this->addLineAttribute('id', $id);
    }
}
