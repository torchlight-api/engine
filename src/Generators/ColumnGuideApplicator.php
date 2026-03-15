<?php

namespace Torchlight\Engine\Generators;

class ColumnGuideApplicator
{
    /**
     * @param  list<int>  $columns
     * @return list<string>
     */
    public static function computeLineClasses(array $columns): array
    {
        $classes = [];

        foreach ($columns as $col) {
            $classes[] = 'torchlight-colguide-'.$col;
        }

        return $classes;
    }

    /**
     * @param  list<int>  $columns
     */
    public static function computeGuideHtml(array $columns): string
    {
        $html = '';

        foreach ($columns as $col) {
            $html .= "<span class='torchlight-colguide torchlight-colguide-{$col}' style='--col: {$col}'></span>";
        }

        return $html;
    }
}
