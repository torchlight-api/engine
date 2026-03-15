<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class ReindexAnnotation extends AbstractAnnotation
{
    public static string $name = 'reindex';

    public function process(ParsedAnnotation $annotation): void
    {
        $range = $this->activeRange();

        if ($annotation->methodArgs != 'null' && ! in_array($annotation->methodArgs, ['vim.relative', 'vim.preserve']) && intval($annotation->methodArgs) != $annotation->methodArgs) {
            return;
        }

        if ($annotation->methodArgs === 'vim.relative' || $annotation->methodArgs === 'vim.preserve') {
            $backwardsCount = 1;
            for ($i = $range->startLine - 2; $i >= 0; $i--) {
                $this->forceDisplayLine($i, $backwardsCount);
                $backwardsCount++;
            }

            if ($annotation->methodArgs === 'vim.preserve') {
                $this->reindexLine($range->startLine - 1, $range->startLine);
            } else {
                $this->reindexLine($range->startLine - 1, 0);
            }

            for ($i = $range->startLine; $i < $this->lineNumbersGutter()->getMaxLineCount(); $i++) {
                $this->reindexLine($i, abs($range->startLine - $i) + 1);
            }

            return;
        }

        $offset = $annotation->methodArgs == 'null' ? null : intval($annotation->methodArgs);
        $wasRelative = false;

        if (str_starts_with((string) $annotation->methodArgs, '-') || str_starts_with((string) $annotation->methodArgs, '+')) {
            $wasRelative = true;
            $offset += $range->startLine;
        }

        if ($wasRelative && $annotation->range === null) {
            $this->reindexLine($range->startLine - 1, $offset, intval($annotation->methodArgs));

            return;
        }

        if ($wasRelative && $range->isSingleLine) {
            $this->reindexLine($range->startLine - 1, $offset);
            $this->reindexLine($range->endLine, $range->startLine + 1);

            return;
        }

        if ($offset == null) {
            for ($i = $range->startLine - 1; $i < $range->endLine; $i++) {
                $this->reindexLine($i, null);
            }

            $this->reindexLine($range->endLine, $range->startLine); // Restart the counting.

            return;
        }

        $this->reindexLine($range->startLine - 1, $offset);
    }
}
