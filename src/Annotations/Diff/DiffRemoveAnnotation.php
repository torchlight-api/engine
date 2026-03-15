<?php

namespace Torchlight\Engine\Annotations\Diff;

use Torchlight\Engine\Annotations\Annotation;

#[Annotation(name: 'remove', aliases: ['--'], charRanges: true)]
class DiffRemoveAnnotation extends AbstractDiffAnnotation
{
    public const DIFF_REMOVE_SCOPES = [
        'markup.deleted',
        'torchlight.markup.deleted',
        'torchlight.markup.deleted.foreground',
    ];

    protected function marker(): string
    {
        return '-';
    }

    protected function classPrefix(): string
    {
        return 'remove';
    }

    protected function scopePrefix(): string
    {
        return 'deleted';
    }
}
