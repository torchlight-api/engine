<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'mark')]
class MarkAnnotation extends AbstractAnnotation
{
    protected function onLine(ParsedAnnotation $annotation): void
    {
        $needle = $annotation->methodArgs ?? '';

        if ($needle === '') {
            return;
        }

        $lineText = $this->annotationEngine->getLineText($this->activeRange()->startLine);

        if ($lineText === null) {
            return;
        }

        $matchAll = in_array('all', $annotation->options);
        $offset = 0;
        $found = false;

        while (($pos = mb_strpos($lineText, $needle, $offset)) !== false) {
            $start = $pos + 1;
            $end = $pos + mb_strlen($needle);
            $found = true;

            $this->annotationEngine->addAttributesToCharacterRange(
                $this->activeRange()->startLine,
                $start,
                $end,
                ['class' => 'char-mark']
            );

            if (! $matchAll) {
                break;
            }

            $offset = $pos + mb_strlen($needle);
        }

        if ($found) {
            $this->addBlockClass('has-mark-lines');
        }
    }
}
