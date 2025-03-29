<?php

namespace Torchlight\Engine\Generators;

use Torchlight\Engine\Generators\Gutters\AbstractGutter;

class GenerationOptions
{
    /**
     * @var AbstractGutter[]
     */
    public array $gutters = [];

    public array $blockClasses = [];

    public array $lineClasses = [];

    public array $lineAttributes = [];

    public array $linePrepends = [];

    public array $lineAppends = [];

    public array $lineContentCallbacks = [];

    public array $lineTokenCallbacks = [];

    public array $textReplacements = [];

    public array $characterDecorators = [];

    public function reset(): void
    {
        foreach ($this->gutters as $gutter) {
            $gutter->reset();
        }

        $this->characterDecorators = [];
        $this->blockClasses = [];
        $this->lineClasses = [];
        $this->lineAttributes = [];
        $this->linePrepends = [];
        $this->lineAppends = [];
        $this->lineContentCallbacks = [];
        $this->lineTokenCallbacks = [];
        $this->textReplacements = [];
    }
}
