<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

#[Annotation(name: 'region')]
class RegionAnnotation extends AbstractAnnotation
{
    /** @var array<string, int> */
    protected array $activeRegions = [];

    public function process(ParsedAnnotation $annotation): void
    {
        $regionName = $annotation->methodArgs ?? 'default';

        // Check for end modifier in options
        $isEnd = in_array('end', $annotation->options);

        if ($isEnd) {
            // Close the region
            if (isset($this->activeRegions[$regionName])) {
                $startLine = $this->activeRegions[$regionName];
                $endLine = $this->activeRange()->startLine;

                $this->annotationEngine->prependLine($startLine, "<div class=\"tl-region\" data-region=\"{$regionName}\">");
                $this->annotationEngine->appendLine($endLine, '</div>');

                $this->addBlockClass('has-regions');

                unset($this->activeRegions[$regionName]);
            }

            return;
        }

        $this->activeRegions[$regionName] = $this->activeRange()->startLine;

        $this->addLineAttribute('data-region', $regionName);
    }

    public function afterProcess(): void
    {
        foreach ($this->activeRegions as $regionName => $startLine) {
            $this->annotationEngine->addAttributeToLine($startLine, 'data-region', $regionName);
        }

        $this->activeRegions = [];
    }

    public function reset(): void
    {
        parent::reset();
        $this->activeRegions = [];
    }
}
