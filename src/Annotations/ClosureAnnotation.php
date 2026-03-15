<?php

namespace Torchlight\Engine\Annotations;

use Closure;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class ClosureAnnotation extends AbstractAnnotation
{
    public static string $name = '__closure__';

    public function __construct(
        AnnotationEngine $annotationEngine,
        protected string $closureName,
        protected Closure $callback,
        protected bool $closureCharRanges = false,
    ) {
        parent::__construct($annotationEngine);
    }

    public static function getName(): string
    {
        return static::$name;
    }

    public static function supportsCharacterRanges(): bool
    {
        return true;
    }

    public function process(ParsedAnnotation $annotation): void
    {
        $context = new AnnotationContext(
            $this->annotationEngine,
            $annotation,
            $this->activeRange(),
        );

        ($this->callback)($context);
    }
}
