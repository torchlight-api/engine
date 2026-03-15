<?php

namespace Torchlight\Engine\Generators\Gutters;

use Phiki\Theme\TokenSettings;
use Phiki\Token\HighlightedToken;
use Torchlight\Engine\Annotations\AnnotationEngine;
use Torchlight\Engine\Contracts\Gutter;
use Torchlight\Engine\Generators\GenerationOptions;
use Torchlight\Engine\Generators\GutterServices;
use Torchlight\Engine\Generators\RenderableToken;
use Torchlight\Engine\Options;

abstract class AbstractGutter implements Gutter
{
    protected Options $options;

    protected ?GutterServices $services = null;

    protected ?GenerationOptions $generationOptions = null;

    protected int $priority = 100;

    protected string $cssClass = '';

    protected bool $userSelectable = false;

    public function __construct(
        protected ?AnnotationEngine $engine = null,
    ) {
        $this->options = Options::default();
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function setGenerationOptions(GenerationOptions $generationOptions): static
    {
        $this->generationOptions = $generationOptions;

        return $this;
    }

    public function setServices(GutterServices $services): static
    {
        $this->services = $services;

        return $this;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->options = $options;

        return $this;
    }

    protected function services(): GutterServices
    {
        if ($this->services === null) {
            throw new \LogicException('Gutter services have not been configured.');
        }

        return $this->services;
    }

    public function reset(): void {}

    /**
     * @param  array<int, RenderableToken>  $tokens
     */
    abstract public function renderLine(int $relativeLine, int $index, array $tokens): string;

    public function renderSpacer(): string
    {
        return '';
    }

    public function shouldRender(): bool
    {
        return true;
    }

    public function decorateLine(int $relativeLine, int $index, GenerationOptions $options): void
    {
        // Override in subclasses to add line classes, styles, or attributes.
    }

    /**
     * @param  list<string>  $extraClasses
     */
    protected function renderGutterSpan(
        string $content,
        ?string $class = null,
        string $colorStyles = '',
        array $extraClasses = [],
    ): string {
        $class ??= $this->cssClass;

        $allClasses = array_filter(array_merge([$class], $extraClasses));
        $classAttr = implode(' ', $allClasses);

        $styles = '';
        if (! $this->userSelectable) {
            $styles .= 'user-select: none;';
        }
        $styles .= $colorStyles;

        return '<span class="'.$classAttr.'" style="'.$styles.'">'.$content.'</span>';
    }

    /**
     * @return array<string, string|null>
     */
    protected function getThemeValueStyles(string $propertyName, string $themeProp, ?string $default = null): array
    {
        return $this->services()->getThemeValueStyles($propertyName, $themeProp, $default);
    }

    /** @param list<string>|string $themeProp */
    protected function getThemeValueStylesString(string $propertyName, array|string $themeProp, ?string $default = null): string
    {
        return $this->services()->getThemeValueStylesString($propertyName, $themeProp, $default);
    }

    protected function getLineNumberColorStyles(): string
    {
        return $this->services()->getLineNumberColorStyles();
    }

    /**
     * @return list<string>
     */
    protected function getTokenStyles(HighlightedToken|RenderableToken $token): array
    {
        return $this->services()->getTokenStyles($token);
    }

    /** @param list<string> $scopes */
    protected function makeToken(string $text, array $scopes): HighlightedToken
    {
        return $this->services()->makeToken($text, $scopes);
    }

    /**
     * @param  list<string>  $scopes
     * @return array<string, TokenSettings>
     */
    protected function getScopeSettings(array $scopes): array
    {
        return $this->services()->getScopeSettings($scopes);
    }

    /** @param list<string> $scopes */
    protected function getScopeStyles(array $scopes): string
    {
        return $this->services()->getScopeStyles($scopes);
    }

    /**
     * @param  list<string>  $scopes
     * @param  list<string>  $classes
     * @param  array<int|string, string>  $styles
     */
    protected function renderText(string $text, array $scopes, array $classes = [], array $styles = []): string
    {
        return $this->services()->renderText($text, $scopes, $classes, $styles);
    }

    /** @param list<string>|string $themeProp */
    protected function registerLineStyle(string $class, array|string $themeProp, string $cssProperty, ?string $default = null): void
    {
        $this->services()->registerLineStyle($class, $themeProp, $cssProperty, $default);
    }
}
