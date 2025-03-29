<?php

namespace Torchlight\Engine\Tests\Loaders;

use Torchlight\Engine\Tests\TestParser;

class TestLoader
{
    /**
     * @return TestBlock[]
     */
    public static function load(?string $test = null): array
    {
        $parser = new TestParser;
        $parsedTests = [];

        foreach (glob(__DIR__.'/../fixtures/tests/*.txt') as $path) {
            $filename = basename($path);

            // Provides a way to temporarily disable a test.
            if (str_starts_with($filename, '_')) {
                continue;
            }

            if ($test && $filename != $test) {
                continue;
            }

            $parsedTests[] = $parser->parse(
                file_get_contents($path),
                $path
            );
        }

        return $parsedTests;
    }
}
