<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'highlight', aliases: ['~~'], charRanges: true)]
class HighlightAnnotation extends AbstractAnnotation
{
    protected function onLine(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-highlight-lines')
            ->addLineClass(['line-highlight', 'line-has-background'])
            ->markLinesHighlighted();
    }

    protected function onCharacterRange(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-highlight-lines')
            ->addStyledCharacterRange('char-highlight', 'line-highlight');
    }
}
