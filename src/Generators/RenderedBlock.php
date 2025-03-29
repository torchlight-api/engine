<?php

namespace Torchlight\Engine\Generators;

class RenderedBlock
{
    public string $code = '';

    public array $attributes = [];

    public string $attributeString = '';

    public array $styles = [];

    public string $styleString = '';

    public array $wrapperStyles = [];

    public string $wrapperStyleString = '';

    public array $wrapperClasses = [];

    public string $wrapperClassString = '';

    public array $classes = [];

    public string $classString = '';

    public function allClassesToString(): string
    {
        return implode(' ',
            array_filter(array_merge($this->wrapperClasses, $this->classes))
        );
    }

    public function allStylesToString(): string
    {
        return $this->wrapperStyleString.$this->styleString;
    }
}
