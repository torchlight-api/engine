<?php

namespace Torchlight\Engine\Annotations\Diff;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class DiffAddAnnotation extends AbstractAnnotation
{
    public static string $name = 'add';

    public static array $aliases = ['++'];

    public const DIFF_ADD_SCOPES = ['markup.inserted', 'torchlight.markup.inserted', 'torchlight.markup.inserted.foreground'];

    public function process(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-add-lines')
            ->addLineClass(['line-add', 'line-has-background']);

        if (! $this->options->diffPreserveSyntaxColors) {
            $this->addLineScope(['markup.inserted', 'torchlight.markup.inserted']);
        }

        if ($this->options->diffIndicatorsEnabled) {
            if ($this->options->diffIndicatorsInPlaceOfNumbers) {
                $this->replaceLineMarker('+', self::DIFF_ADD_SCOPES);
            } else {
                $this->setDiffLineMarker('+')
                    ->setLineScopes(self::DIFF_ADD_SCOPES);
            }
        } else {
            $this->setLineScopes(self::DIFF_ADD_SCOPES);
        }
    }
}
