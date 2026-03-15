<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'hide')]
class HideAnnotation extends AbstractAnnotation
{
    /** @var array<int, array{start: int, end: int, placeholder: string}> */
    protected array $hiddenRanges = [];

    public function process(ParsedAnnotation $annotation): void
    {
        $placeholder = $annotation->methodArgs ?? '...';
        $range = $this->activeRange();

        $this->hiddenRanges[] = [
            'start' => $range->startLine,
            'end' => $range->endLine,
            'placeholder' => $placeholder,
        ];
    }

    public function afterProcess(): void
    {
        if (empty($this->hiddenRanges)) {
            return;
        }

        $this->addBlockClass('has-hidden-lines');

        foreach ($this->hiddenRanges as $range) {
            $count = $range['end'] - $range['start'] + 1;
            $placeholder = htmlspecialchars($range['placeholder']);

            $this->annotationEngine->addLineClass($range['start'], 'line-elided');
            $this->annotationEngine->addAttributeToLine($range['start'], 'data-hidden-count', (string) $count);

            $this->annotationEngine->modifyLineContents($range['start'], fn (string $content): string => '<span class="tl-elision">'.$placeholder.'</span>');

            for ($i = $range['start'] + 1; $i <= $range['end']; $i++) {
                $this->annotationEngine->addLineClass($i, 'line-hidden');
            }
        }

        $this->hiddenRanges = [];
    }

    public function reset(): void
    {
        parent::reset();
        $this->hiddenRanges = [];
    }
}
