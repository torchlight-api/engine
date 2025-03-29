<?php

namespace Torchlight\Engine\Tests\Loaders;

use Torchlight\Engine\Support\Str;

class SamplesLoader
{
    protected static function parse(string $path): array
    {
        $parts = explode(
            '===============================================',
            file_get_contents($path),
            2
        );

        return [
            array_merge(
                [basename($path, '.txt')],
                Str::nlSplit(trim($parts[0]))
            ),
            Str::nlSplit(trim($parts[1])),
        ];
    }

    public static function load(): array
    {
        $samples = [];

        foreach (glob(__DIR__.'/../fixtures/annotations/*.txt') as $path) {
            $samples[] = self::parse($path);
        }

        return $samples;
    }
}
