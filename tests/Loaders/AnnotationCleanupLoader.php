<?php

namespace Torchlight\Engine\Tests\Loaders;

class AnnotationCleanupLoader
{
    public static function load(): array
    {
        $samples = SamplesLoader::load();
        $dataset = [];

        foreach ($samples as $sample) {
            $commentStyle = $sample[0][1];
            $codeLines = $sample[1];

            $noAnnotation = implode("\n", $codeLines);

            if (str_contains($commentStyle, '|')) {
                $parts = explode('|', $commentStyle);
                $open = $parts[0];
                $close = $parts[1];

                $codeLines[0] = $codeLines[0].' '.$open.' [tl! highlight] '.$close;
            } else {
                $codeLines[0] = $codeLines[0].' '.$commentStyle.' [tl! highlight]';

            }

            $dataset[] = [
                $sample[0][0],
                $commentStyle,
                implode("\n", $codeLines),
                $noAnnotation,
            ];
        }

        return $dataset;
    }
}
