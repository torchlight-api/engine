<?php

namespace Torchlight\Engine\Generators;

use Phiki\Highlighting\Highlighter;
use Phiki\Theme\TokenSettings;
use Phiki\Token\HighlightedToken;

class GutterServices
{
    public function __construct(
        protected HtmlGenerator $generator,
        protected ThemeStyleResolver $resolver,
        protected Highlighter $highlighter,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function getThemeValueStyles(string $propertyName, string $themeProp, ?string $default = null): array
    {
        return $this->resolver->getThemeValueStyles(
            $propertyName,
            $themeProp,
            $default
        );
    }

    /** @param list<string>|string $themeProp */
    public function getThemeValueStylesString(string $propertyName, array|string $themeProp, ?string $default = null): string
    {
        return $this->resolver->getThemeValueStylesString(
            $propertyName,
            $themeProp,
            $default
        );
    }

    public function getLineNumberColorStyles(): string
    {
        return $this->getThemeValueStylesString(
            'color',
            [
                'torchlight.lineNumberColor',
                'editorLineNumber.foreground',
            ]
        );
    }

    /**
     * @return list<string>
     */
    public function getTokenStyles(HighlightedToken|RenderableToken $token): array
    {
        $highlighted = $token instanceof RenderableToken ? $token->highlighted : $token;

        return $this->resolver->getTokenStyles($highlighted);
    }

    /** @param list<string> $scopes */
    public function makeToken(string $text, array $scopes): HighlightedToken
    {
        return $this->resolver->makeToken($text, $scopes);
    }

    /**
     * @param  list<string>  $scopes
     * @return array<string, TokenSettings>
     */
    public function getScopeSettings(array $scopes): array
    {
        return $this->resolver->getScopeSettings($scopes);
    }

    /** @param list<string> $scopes */
    public function getScopeStyles(array $scopes): string
    {
        return $this->resolver->getScopeStyles($scopes);
    }

    /**
     * @param  array<string, TokenSettings>  $settings
     */
    public function getSettingsStyleString(array $settings): string
    {
        return $this->resolver->getSettingsStyleString($settings);
    }

    /**
     * @param  list<string>  $scopes
     * @param  list<string>  $classes
     * @param  array<int|string, string>  $styles
     */
    public function renderText(string $text, array $scopes, array $classes = [], array $styles = []): string
    {
        return $this->generator->buildToken(
            RenderableToken::from($this->makeToken($text, $scopes)),
            $classes,
            $styles
        );
    }

    /** @param list<string>|string $themeProp */
    public function registerLineStyle(string $class, array|string $themeProp, string $cssProperty, ?string $default = null): static
    {
        $this->resolver->registerLineStyle(
            $class,
            $themeProp,
            $cssProperty,
            $default
        );

        return $this;
    }

    public function getResolver(): ThemeStyleResolver
    {
        return $this->resolver;
    }
}
