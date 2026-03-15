<?php

namespace Torchlight\Engine\Generators\BlockDecorators;

use Torchlight\Engine\Contracts\BlockDecorator;
use Torchlight\Engine\Generators\RenderContext;

class CopyTargetDecorator implements BlockDecorator
{
    protected string $cssClass = 'torchlight-copy-target';

    protected string $tagName = 'div';

    /** @var array<string, string|bool> */
    protected array $attributes = [
        'aria-hidden' => 'true',
        'hidden' => true,
        'tabindex' => '-1',
        'style' => 'display: none;',
    ];

    protected int $priority = 100;

    public function setCssClass(string $class): static
    {
        $this->cssClass = $class;

        return $this;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setTagName(string $tag): static
    {
        $this->tagName = $tag;

        return $this;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    /** @param array<string, string|bool> $attributes */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param  string  $name  Attribute name
     * @param  string|bool  $value  Attribute value (true for valueless)
     */
    public function setAttribute(string $name, string|bool $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function removeAttribute(string $name): static
    {
        unset($this->attributes[$name]);

        return $this;
    }

    /** @return array<string, string|bool> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function shouldRender(RenderContext $context): bool
    {
        return $context->options->copyable;
    }

    public function render(RenderContext $context, string $cleanedText): string
    {
        $content = htmlspecialchars($cleanedText);
        $attrs = $this->buildAttributeString();

        return "<{$this->tagName} {$attrs}class='{$this->cssClass}'>{$content}</{$this->tagName}>";
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    protected function buildAttributeString(): string
    {
        $parts = [];

        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
            } elseif ($value !== false && $value !== null) {
                $parts[] = $name."='".$value."'";
            }
        }

        if (empty($parts)) {
            return '';
        }

        return implode(' ', $parts).' ';
    }
}
