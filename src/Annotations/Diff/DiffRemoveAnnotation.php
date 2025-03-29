<?php

namespace Torchlight\Engine\Annotations\Diff;

use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class DiffRemoveAnnotation extends AbstractAnnotation
{
    public static string $name = 'remove';

    public static array $aliases = ['--'];

    public const DIFF_REMOVE_SCOPES = ['markup.deleted', 'torchlight.markup.deleted', 'torchlight.markup.deleted.foreground'];

    public function process(ParsedAnnotation $annotation): void
    {
        $this->addBlockClass('has-remove-lines')
            ->addLineClass(['line-remove', 'line-has-background']);

        if (! $this->options->diffPreserveSyntaxColors) {
            $this->addLineScope(['markup.deleted', 'torchlight.markup.deleted']);
        }

        if ($this->options->diffIndicatorsEnabled) {
            if ($this->options->diffIndicatorsInPlaceOfNumbers) {
                $this->replaceLineMarker('-', self::DIFF_REMOVE_SCOPES);
            } else {
                $this
                    ->setDiffLineMarker('-')
                    ->setLineScopes(self::DIFF_REMOVE_SCOPES);
            }
        } else {
            $this->setLineScopes(self::DIFF_REMOVE_SCOPES);
        }
    }
}
