<?php

namespace Torchlight\Engine\Annotations\Parser;

enum AnnotationType: int
{
    case Named = 1;
    case ClassName = 2;
    case IdAttribute = 3;
}
