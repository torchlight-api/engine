<?php

namespace Torchlight\Engine\Annotations\Ranges;

enum RangeType: int
{
    case Relative = 1;
    case OpenEndedStart = 2;
    case OpenEndedEnd = 3;
    case All = 4;
    case Character = 5;
}
