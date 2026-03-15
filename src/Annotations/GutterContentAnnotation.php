<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Generators\Gutters\CustomContentGutter;

#[Annotation(name: 'gutter')]
class GutterContentAnnotation extends AbstractAnnotation
{
    protected function onLine(ParsedAnnotation $annotation): void
    {
        [$content, $gutterName] = $this->parseGutterArgs($annotation);

        $this->addBlockClass('has-custom-gutter');

        if ($content !== '') {
            $gutter = $this->resolveGutter($gutterName);

            $this->eachLine(function (int $i) use ($gutter, $content): void {
                $gutter->setLineContent($i, $content);
            });
        }
    }

    private function parseGutterArgs(ParsedAnnotation $annotation): array
    {
        $raw = $annotation->rawMethodArgs;

        if ($raw === null) {
            return ['', 'custom-content'];
        }

        $parts = array_map(
            static fn (?string $part): string => trim((string) $part),
            str_getcsv($raw, ',', '"', '')
        );

        $content = isset($parts[0]) ? trim($parts[0], '"\'') : '';
        $name = isset($parts[1]) ? trim($parts[1], '"\'') : 'custom-content';

        return [$content, $name];
    }

    private function resolveGutter(string $name): CustomContentGutter
    {
        if (! $this->annotationEngine->hasGutter($name)) {
            $this->annotationEngine->addGutter(
                $name,
                (new CustomContentGutter)->setPriority(300)
            );
        }

        $gutter = $this->annotationEngine->getGenerationOptions()->gutters[$name];

        if (! $gutter instanceof CustomContentGutter) {
            throw new \LogicException("Gutter [{$name}] is not a custom content gutter.");
        }

        return $gutter;
    }
}
