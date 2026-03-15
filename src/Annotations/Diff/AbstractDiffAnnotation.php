<?php

namespace Torchlight\Engine\Annotations\Diff;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

abstract class AbstractDiffAnnotation extends AbstractAnnotation
{
    abstract protected function marker(): string;

    abstract protected function classPrefix(): string;

    abstract protected function scopePrefix(): string;

    public function process(ParsedAnnotation $annotation): void
    {
        $prefix = $this->classPrefix();
        $scopes = $this->diffScopes();

        if ($this->isCharacterRange()) {
            $this->addBlockClass("has-{$prefix}-lines")
                ->addStyledCharacterRange("char-{$prefix}", "line-{$prefix}");

            return;
        }

        $this->addBlockClass("has-{$prefix}-lines")
            ->addLineClass(["line-{$prefix}", 'line-has-background']);

        if (! $this->options->diffPreserveSyntaxColors) {
            $scopePrefix = $this->scopePrefix();
            $this->addLineScope(["markup.{$scopePrefix}", "torchlight.markup.{$scopePrefix}"]);
        }

        if ($this->options->diffIndicatorsEnabled) {
            if ($this->options->diffIndicatorsInPlaceOfNumbers) {
                $this->replaceLineMarker($this->marker(), $scopes);
            } else {
                $this->setDiffLineMarker($this->marker())
                    ->setLineScopes($scopes);
            }
        } else {
            $this->setLineScopes($scopes);
        }
    }

    /** @return list<string> */
    protected function diffScopes(): array
    {
        $prefix = $this->scopePrefix();

        return ["markup.{$prefix}", "torchlight.markup.{$prefix}", "torchlight.markup.{$prefix}.foreground"];
    }
}
