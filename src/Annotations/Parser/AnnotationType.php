<?php

namespace Torchlight\Engine\Annotations\Parser;

enum AnnotationType: int
{
    case Named = 1;    // Standard named annotations: highlight, focus, etc.
    case Prefixed = 2; // Any prefix-based annotation: .class, #id, etc.
}
