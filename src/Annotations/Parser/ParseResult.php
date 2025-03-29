<?php

namespace Torchlight\Engine\Annotations\Parser;

class ParseResult
{
    public string $text = '';

    /**
     * @var ParsedAnnotation[]
     */
    public array $annotations = [];
}
