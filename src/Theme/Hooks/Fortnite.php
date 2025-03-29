<?php

namespace Torchlight\Engine\Theme\Hooks;

use Torchlight\Engine\Options;

class Fortnite
{
    public static function replaceColors(string $html, Options $options, string $propertyPrefix, string $themeName): string
    {
        $replacements = [
            [
                '/'.$propertyPrefix.'color:\s*#ea5a9c;/i',
                $propertyPrefix.'color: #ef5ea0; '.$propertyPrefix.'text-shadow: 0 0 2px #000000, 0 0 8px #bf226a, 0 0 2px #ef5ea0;',
            ],
            [
                '/'.$propertyPrefix.'color:\s*#ea5a9c;/i',
                $propertyPrefix.'color: #ef5ea0; '.$propertyPrefix.'text-shadow: 0 0 2px #000000, 0 0 8px #bf226a, 0 0 2px #ef5ea0;',
            ],
            [
                '/'.$propertyPrefix.'color:\s*#f9dea6;/i',
                $propertyPrefix.'color: #efe5d3; '.$propertyPrefix.'text-shadow: 0 0 2px #0e0119, 0 0 8px #ef7b05cc, 0 0 2px #f3a007cc;',
            ],
            [
                '/'.$propertyPrefix.'color:\s*#8cefd8;/i',
                $propertyPrefix.'color: #9eecda; '.$propertyPrefix.'text-shadow: 0 0 1px #0e0119, 0 0 6px #16ccded9cc, 0 0 2px #9eecdacc;',
            ],
            [
                '/'.$propertyPrefix.'color:\s*#cfe08a;/i',
                $propertyPrefix.'color: #cfe08a; '.$propertyPrefix.'text-shadow: 0 0 2px #000000, 0 0 5px #5ca2cc;',
            ],
        ];

        foreach ($replacements as $replacement) {
            $html = preg_replace($replacement[0], $replacement[1], $html);
        }

        return $html;
    }
}
