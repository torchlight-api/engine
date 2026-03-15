<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'mono', charRanges: true)]
class MonoAnnotation extends AbstractAnnotation
{
    protected function onLine(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-mono-lines')
            ->addLineClass(['line-mono']);
    }

    protected function onCharacterRange(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-mono-lines')
            ->addAttributesToCharacterRange(['class' => 'char-mono']);
    }
}
