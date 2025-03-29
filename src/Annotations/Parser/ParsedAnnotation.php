<?php

namespace Torchlight\Engine\Annotations\Parser;

use Torchlight\Engine\Annotations\Ranges\AnnotationRange;

class ParsedAnnotation
{
    public int $index = 0;

    public int $sourceLine = 0;

    public string $name = '';

    public string $text = '';

    public ?string $methodArgs = null;

    public array $options = [];

    public ?AnnotationRange $range = null;

    public AnnotationType $type = AnnotationType::Named;
}
