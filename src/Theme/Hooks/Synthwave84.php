<?php

namespace Torchlight\Engine\Theme\Hooks;

use Torchlight\Engine\Options;

class Synthwave84
{
    protected static array $opacities = [
        100 => 'FF',
        95 => 'F2',
        90 => 'E6',
        85 => 'D9',
        80 => 'CC',
        75 => 'BF',
        70 => 'B3',
        65 => 'A6',
        60 => '99',
        55 => '8C',
        50 => '80',
        45 => '73',
        40 => '66',
        35 => '59',
        30 => '4D',
        25 => '40',
        20 => '33',
        15 => '26',
        10 => '1A',
        5 => '0D',
        0 => '00',
    ];

    public static function replaceColors(string $html, Options $options, string $propertyPrefix, string $themeName): string
    {
        $opacity = static::$opacities[50];

        $pattern = '/'.$propertyPrefix.'color:\s*#fe4450;/i';
        $replacement = $propertyPrefix.'color: #fff5f6; '.$propertyPrefix.'text-shadow: 0 0 2px #000, 0 0 10px #fc1f2c'.$opacity.
            ', 0 0 5px #fc1f2c'.$opacity.
            ', 0 0 25px #fc1f2c'.$opacity.';';
        $html = preg_replace($pattern, $replacement, $html);

        $pattern = '/'.$propertyPrefix.'color:\s*#ff7edb;/i';
        $replacement = $propertyPrefix.'color: #f92aad; '.$propertyPrefix.'text-shadow: 0 0 2px #100c0f, 0 0 5px #dc078e33, 0 0 10px #fff3;';
        $html = preg_replace($pattern, $replacement, $html);

        $pattern = '/'.$propertyPrefix.'color:\s*#fede5d;/i';
        $replacement = $propertyPrefix.'color: #f4eee4; '.$propertyPrefix.'text-shadow: 0 0 2px #393a33, 0 0 8px #f39f05'.$opacity.
            ', 0 0 2px #f39f05'.$opacity.';';
        $html = preg_replace($pattern, $replacement, $html);

        $pattern = '/'.$propertyPrefix.'color:\s*#72f1b8;/i';
        $replacement = $propertyPrefix.'color: #72f1b8; '.$propertyPrefix.'text-shadow: 0 0 2px #100c0f, 0 0 10px #257c55'.$opacity.
            ', 0 0 35px #212724'.$opacity.';';
        $html = preg_replace($pattern, $replacement, $html);

        $pattern = '/'.$propertyPrefix.'color:\s*#36f9f6;/i';
        $replacement = $propertyPrefix.'color: #fdfdfd; '.$propertyPrefix.'text-shadow: 0 0 2px #001716, 0 0 3px #03edf9'.$opacity.
            ', 0 0 5px #03edf9'.$opacity.
            ', 0 0 8px #03edf9'.$opacity.';';
        $html = preg_replace($pattern, $replacement, $html);

        return $html;
    }
}
