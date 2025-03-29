<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class HighlightAnnotation extends AbstractAnnotation
{
    public static string $name = 'highlight';

    public static array $aliases = ['~~'];

    public function process(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-highlight-lines')
            ->addLineClass(['line-highlight', 'line-has-background'])
            ->markLinesHighlighted();
    }
}
