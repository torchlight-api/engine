<?php

namespace Torchlight\Engine\Annotations\Diff;

use Torchlight\Engine\Annotations\Annotation;

#[Annotation(name: 'add', aliases: ['++'], charRanges: true)]
class DiffAddAnnotation extends AbstractDiffAnnotation
{
    public const DIFF_ADD_SCOPES = [
        'markup.inserted',
        'torchlight.markup.inserted',
        'torchlight.markup.inserted.foreground',
    ];

    protected function marker(): string
    {
        return '+';
    }

    protected function classPrefix(): string
    {
        return 'add';
    }

    protected function scopePrefix(): string
    {
        return 'inserted';
    }
}
