<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'focus', aliases: ['**'], charRanges: true)]
class FocusAnnotation extends AbstractAnnotation
{
    protected function onLine(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-focus-lines')
            ->addLineClass('line-focus');
    }

    protected function onCharacterRange(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-focus-lines')
            ->addAttributesToCharacterRange(['class' => 'char-focus']);
    }
}
