<?php

namespace Torchlight\Engine\Generators;

class TokenMetadata
{
    /**
     * @param  list<string>  $classes
     * @param  array<string, string>  $attributes
     */
    public function __construct(
        public array $classes = [],
        public array $attributes = [],
        public ?string $rawContent = null,
    ) {}

    public function hasClasses(): bool
    {
        return ! empty($this->classes);
    }

    public function hasAttributes(): bool
    {
        return ! empty($this->attributes);
    }

    public function isRaw(): bool
    {
        return $this->rawContent !== null;
    }

    public function addClass(string $class): static
    {
        $this->classes[] = $class;

        return $this;
    }

    public function addAttribute(string $name, string $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function setRawContent(string $content): static
    {
        $this->rawContent = $content;

        return $this;
    }

    public function merge(TokenMetadata $other): static
    {
        $this->classes = array_merge($this->classes, $other->classes);
        $this->attributes = array_merge($this->attributes, $other->attributes);

        if ($other->rawContent !== null) {
            $this->rawContent = $other->rawContent;
        }

        return $this;
    }
}
