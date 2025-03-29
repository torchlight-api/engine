<?php

namespace Torchlight\Engine\Theme\Hooks;

use Torchlight\Engine\Options;

class Moonlight
{
    public static function replaceColors(string $html, Options $options, string $propertyPrefix, string $themeName): string
    {
        $replacements = [
            [
                '/'.$propertyPrefix.'color:\s*#82AAFF;/i',
                $propertyPrefix.'color: #91bbff; '.$propertyPrefix.'text-shadow: 0 0 10px #2f36ff, 0 0 22px #9d91ff, 0 0 2px black;',
            ],
            [
                '/'.$propertyPrefix.'color:\s*#65BCFF;/i',
                $propertyPrefix.'color: #67d2ff; '.$propertyPrefix.'text-shadow: 0 0 15px #12baff, 0 0 2px black;',
            ],
        ];

        foreach ($replacements as $replacement) {
            $html = preg_replace($replacement[0], $replacement[1], $html);
        }

        return $html;
    }
}
