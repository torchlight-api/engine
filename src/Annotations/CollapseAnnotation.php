<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class CollapseAnnotation extends AbstractAnnotation
{
    public static string $name = 'collapse';

    protected function getCollapseIndicator(): string
    {
        $indicatorText = $this->options->summaryCollapsedIndicator ?? '...';

        $styles = $this->getLineNumberColorStyles();

        return '<span class="summary-hide-when-open" style="'.$styles.'">'.$indicatorText.'</span>';
    }

    protected function insertBeforeFinalSpanIfNewline(string $html, string $insertion): string
    {
        $pattern = '/(.*)(<span[^>]*>(.*?)<\/span>)$/s';

        if (preg_match($pattern, $html, $m)) {
            $beforeSpan = $m[1];
            $finalSpan = $m[2];
            $innerText = $m[3];

            if (str_contains($innerText, "\n")) {
                return $beforeSpan.$insertion.$finalSpan;
            }
        }

        return $html.$insertion;
    }

    public function process(ParsedAnnotation $annotation): void
    {
        $attributes = '';

        if (in_array('open', $annotation->options)) {
            $attributes = ' open';
        }

        $this
            ->addBlockClass('has-summaries')
            ->surroundRange("<details{$attributes}>", '</details>')
            ->surroundStartLine('<summary style="cursor: pointer; display: block;">', '</summary>')
            ->modifyStartLineContents(function ($content) {
                return $this->insertBeforeFinalSpanIfNewline($content, $this->getCollapseIndicator());
            });

        $this->collapseGutter()->markRange($this->range);
    }
}
