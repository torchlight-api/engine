<?php

namespace Torchlight\Engine\Tests;

class TestBlock
{
    public function __construct(
        public string $content,
        public string $code,
        public string $expect,
        public string $style,
        public array $config,
        public array $request,
        public ?string $filePath,
    ) {}

    public function save(string $lastResult): void
    {
        if (! $this->filePath) {
            return;
        }

        file_put_contents(
            $this->filePath,
            $this->content."\n".$lastResult
        );
    }
}
