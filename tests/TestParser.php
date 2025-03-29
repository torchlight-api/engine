<?php

namespace Torchlight\Engine\Tests;

use Torchlight\Engine\Support\Str;

class TestParser
{
    public function parse(string $testContent, ?string $filePath = null): TestBlock
    {
        $request = [];
        $block = [];
        $clean = [];
        $expect = [];
        $style = [];
        $section = 'code';

        $lines = Str::nlSplit($testContent);
        $cleaned = [];

        foreach ($lines as $line) {
            $cleaned[] = $line;
            $trimmedLine = trim($line);

            if (str_starts_with($trimmedLine, ':::request')) {
                $request = json_decode(Str::after($trimmedLine, ':::request'), true);

                continue;
            }

            if (str_starts_with($trimmedLine, ':::config')) {
                $block = json_decode(Str::after($trimmedLine, ':::config'), true);

                continue;
            }

            if (str_starts_with($trimmedLine, ':::expectation')) {
                $section = 'expectation';

                continue;
            }

            if (str_starts_with($trimmedLine, ':::style')) {
                $section = 'style';

                continue;
            }

            if (str_starts_with($trimmedLine, ':::end')) {
                break;
            }

            switch ($section) {
                case 'code':
                    $code[] = $line;
                    break;
                case 'style':
                    $style[] = $line;
                    break;
                case 'expectation':
                    $expect[] = $line;
                    break;
            }
        }

        $style = implode("\n", $style);
        $content = implode("\n", $cleaned);
        $code = implode("\n", $code);
        $expect = implode("\n", $expect);

        return new TestBlock(
            $content,
            $code,
            $expect,
            $style,
            $block ?? [],
            $request ?? [],
            $filePath
        );
    }
}
