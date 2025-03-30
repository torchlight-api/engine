<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class ReindexAnnotation extends AbstractAnnotation
{
    public static string $name = 'reindex';

    public function process(ParsedAnnotation $annotation): void
    {
        if ($annotation->methodArgs != 'null' && ! in_array($annotation->methodArgs, ['vim.relative', 'vim.preserve']) && intval($annotation->methodArgs) != $annotation->methodArgs) {
            return;
        }

        if ($annotation->methodArgs === 'vim.relative' || $annotation->methodArgs === 'vim.preserve') {
            $backwardsCount = 1;
            for ($i = $this->range->startLine - 2; $i >= 0; $i--) {
                $this->forceDisplayLine($i, $backwardsCount);
                $backwardsCount++;
            }

            if ($annotation->methodArgs === 'vim.preserve') {
                $this->reindexLine($this->range->startLine - 1, $this->range->startLine);
            } else {
                $this->reindexLine($this->range->startLine - 1, 0);
            }

            for ($i = $this->range->startLine; $i < $this->lineNumbersGutter()->getMaxLineCount(); $i++) {
                $this->reindexLine($i, abs($this->range->startLine - $i) + 1);
            }

            return;
        }

        $offset = $annotation->methodArgs == 'null' ? null : intval($annotation->methodArgs);
        $wasRelative = false;

        if (str_starts_with($annotation->methodArgs, '-') || str_starts_with($annotation->methodArgs, '+')) {
            $wasRelative = true;
            $offset += $this->range->startLine;
        }

        if ($wasRelative && $annotation->range === null) {
            $this->reindexLine($this->range->startLine - 1, $offset, intval($annotation->methodArgs));

            return;
        }

        if ($wasRelative && $this->range->isSingleLine) {
            $this->reindexLine($this->range->startLine - 1, $offset);
            $this->reindexLine($this->range->endLine, $this->range->startLine + 1);

            return;
        }

        if ($offset == null) {
            for ($i = $this->range->startLine - 1; $i < $this->range->endLine; $i++) {
                $this->reindexLine($i, null);
            }

            $this->reindexLine($this->range->endLine, $this->range->startLine); // Restart the counting.

            return;
        }

        $this->reindexLine($this->range->startLine - 1, $offset);
    }
}
