<?php

namespace Torchlight\Engine\Generators;

use Phiki\Theme\ParsedTheme;
use Phiki\Theme\TokenSettings;
use Torchlight\Engine\Options;

class RenderContext
{
    /**
     * @param  array<string, ParsedTheme>  $themes
     */
    public function __construct(
        public readonly Options $options,
        public readonly array $themes,
        public readonly string $grammarName,
        private readonly ThemeStyleResolver $themeResolver,
        private readonly HtmlGenerator $generator,
    ) {}

    /**
     * @param  list<string>  $scopes
     * @return array<string, TokenSettings>
     */
    public function getScopeSettings(array $scopes): array
    {
        return $this->themeResolver->getScopeSettings($scopes);
    }

    /** @param list<string> $scopes */
    public function getScopeStyles(array $scopes): string
    {
        return $this->themeResolver->getScopeStyles($scopes);
    }

    /**
     * @param  list<string>|string  $themeProp
     * @return array<string, string|null>
     */
    public function getThemeValueStyles(string $propertyName, array|string $themeProp, ?string $default = null): array
    {
        return $this->themeResolver->getThemeValueStyles($propertyName, $themeProp, $default);
    }

    /** @param list<string>|string $themeProp */
    public function getThemeValueStylesString(string $propertyName, array|string $themeProp, ?string $default = null): string
    {
        return $this->themeResolver->getThemeValueStylesString($propertyName, $themeProp, $default);
    }

    /**
     * @param  list<string>  $classes
     * @param  array<int|string, string>  $styles
     */
    public function buildToken(RenderableToken $token, array $classes = [], array $styles = []): string
    {
        return $this->generator->buildToken($token, $classes, $styles);
    }

    public function getDefaultTheme(): ParsedTheme
    {
        return $this->themeResolver->getDefaultTheme();
    }

    public function getDefaultThemeId(): string
    {
        return $this->themeResolver->getDefaultThemeId();
    }

    /** @param array<string, string> $attributes */
    public function toAttributeString(array $attributes): string
    {
        return $this->themeResolver->toAttributeString($attributes);
    }

    /** @param array<int|string, string|null> $styles */
    public function toStyleString(array $styles): string
    {
        return $this->themeResolver->toStyleString($styles);
    }
}
