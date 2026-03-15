<?php

namespace Torchlight\Engine\Concerns;

trait ManagesReplacers
{
    /** @var array<string, string> */
    protected array $stringReplacers = [];

    /** @var list<callable(string): string> */
    protected array $callableReplacers = [];

    /**
     * @param  string|callable  $search  String to find, or a callable replacer
     * @param  string|null  $replace  Replacement string (required when $search is a string)
     */
    public function addReplacer(string|callable $search, ?string $replace = null): static
    {
        if (is_string($search)) {
            $this->stringReplacers[$search] = $replace ?? '';

            return $this;
        }

        $this->callableReplacers[] = $search;

        return $this;
    }

    /** @param array<string, string>|list<callable(string): string> $replacers */
    public function addReplacers(array $replacers): static
    {
        foreach ($replacers as $key => $value) {
            if (is_string($key)) {
                $this->addReplacer($key, is_string($value) ? $value : null);
            } elseif (is_callable($value)) {
                /** @var callable(string): string $value */
                $this->addReplacer($value);
            }
        }

        return $this;
    }

    public function clearReplacers(): static
    {
        $this->stringReplacers = [];
        $this->callableReplacers = [];

        return $this;
    }

    protected function applyReplacers(string $html): string
    {
        if (count($this->stringReplacers) > 0) {
            $html = strtr($html, $this->stringReplacers);
        }

        foreach ($this->callableReplacers as $replacer) {
            $html = $replacer($html);
        }

        return $html;
    }
}
