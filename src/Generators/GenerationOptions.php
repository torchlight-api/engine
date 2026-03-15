<?php

namespace Torchlight\Engine\Generators;

use Torchlight\Engine\Generators\Gutters\AbstractGutter;

class GenerationOptions
{
    /**
     * @var AbstractGutter[]
     */
    public array $gutters = [];

    /** @var array<string, int|true> */
    public array $blockClasses = [];

    /** @var array<int, list<string>> */
    public array $lineClasses = [];

    /** @var array<int, array<string, string>> */
    public array $lineAttributes = [];

    /** @var array<int, list<string>> */
    public array $linePrepends = [];

    /** @var array<int, list<string>> */
    public array $lineAppends = [];

    /** @var array<int, list<callable(string, array<int, RenderableToken>): string>> */
    public array $lineContentCallbacks = [];

    /** @var array<int, list<callable(array<int, RenderableToken>): array<int, RenderableToken>>> */
    public array $lineTokenCallbacks = [];

    /** @var array<string, string> */
    public array $textReplacements = [];

    /** @var array<int, list<array<string, int|string>>> */
    public array $characterDecorators = [];

    /** @var array<int, true> */
    public array $removedLines = [];

    /** @var list<string> */
    public array $globalLineClasses = [];

    public string $columnGuideHtml = '';

    /** @var array<string, int> */
    public array $codelensIndentPlaceholders = [];

    public bool $hasSeparatePaddingGutter = false;

    public ?GutterServices $gutterServices = null;

    /**
     * @return AbstractGutter[]
     */
    public function getSortedGutters(): array
    {
        $sorted = array_values($this->gutters);
        usort($sorted, fn (AbstractGutter $a, AbstractGutter $b) => $a->getPriority() <=> $b->getPriority());

        return $sorted;
    }

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
        $this->removedLines = [];
        $this->hasSeparatePaddingGutter = false;
        $this->globalLineClasses = [];
        $this->columnGuideHtml = '';
        $this->codelensIndentPlaceholders = [];
    }
}
