<?php

namespace Torchlight\Engine\Tests\Results;

class Line
{
    public string $id = '';

    public array $classes = [];

    public string $text = '';

    public string $lineNumberContent = '';

    public function hasClass(string $class): bool
    {
        return in_array($class, $this->classes);
    }

    public function isHighlighted(): bool
    {
        return $this->hasClass('line-highlight');
    }

    public function hasBackground(): bool
    {
        return $this->hasClass('line-has-background');
    }
}
