<?php

namespace Torchlight\Engine\Tests\Results;

class Result
{
    /**
     * @var Line[]
     */
    protected array $lines = [];

    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    public function lines(): array
    {
        return $this->lines;
    }

    public function line(int $line): ?Line
    {
        return $this->lines[$line - 1] ?? null;
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }
}
