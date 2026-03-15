<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'link', charRanges: true)]
class LinkAnnotation extends AbstractAnnotation
{
    protected function onLine(ParsedAnnotation $annotation): void
    {
        $href = $annotation->methodArgs ?? '';

        if ($href === '') {
            return;
        }

        $escapedHref = htmlspecialchars($href);

        $this->addBlockClass('has-links');

        $this->modifyRangeContents(fn (string $content): string => '<a href="'.$escapedHref.'" class="tl-link">'.$content.'</a>');
    }

    protected function onCharacterRange(ParsedAnnotation $annotation): void
    {
        $href = $annotation->methodArgs ?? '';

        if ($href === '') {
            return;
        }

        $this->addBlockClass('has-links')
            ->addAttributesToCharacterRange([
                'class' => 'tl-link',
                'data-href' => $href,
            ]);
    }
}
