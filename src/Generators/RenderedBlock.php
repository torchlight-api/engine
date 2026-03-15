<?php

namespace Torchlight\Engine\Generators;

class RenderedBlock
{
    public string $code = '';

    /** @var array<string, string> */
    public array $attributes = [];

    public string $attributeString = '';

    /** @var array<string, string> */
    public array $styles = [];

    public string $styleString = '';

    /** @var list<string> */
    public array $wrapperStyles = [];

    public string $wrapperStyleString = '';

    /** @var list<string> */
    public array $wrapperClasses = [];

    public string $wrapperClassString = '';

    /** @var list<string> */
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

    public function toHtml(): string
    {
        $code = "<code {$this->attributeString} class='{$this->allClassesToString()}' style='{$this->allStylesToString()}'>"
            .$this->code
            .'</code>';

        return '<pre>'.$code.'</pre>';
    }
}
