<?php

namespace Torchlight\Engine\Annotations\Ranges;

class AnnotationRange
{
    public RangeType $type = RangeType::Relative;

    public null|string|int $start = null;

    public null|string|int $end = null;
}
