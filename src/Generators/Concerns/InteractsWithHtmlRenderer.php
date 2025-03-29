<?php

namespace Torchlight\Engine\Generators\Concerns;

use Phiki\Highlighter;
use Phiki\Token\HighlightedToken;
use Phiki\Token\Token;
use Torchlight\Engine\Generators\HtmlGenerator;

trait InteractsWithHtmlRenderer
{
    protected ?HtmlGenerator $htmlGenerator = null;

    protected ?Highlighter $highlighter = null;

    public function setHtmlGenerator(HtmlGenerator $generator): static
    {
        $this->htmlGenerator = $generator;

        return $this;
    }

    protected function getThemeValueStyles(string $propertyName, string $themeProp, ?string $default = null): array
    {
        return $this->htmlGenerator->getThemeValueStyles($propertyName, $themeProp, $default);
    }

    protected function getLineNumberColorStyles(): string
    {
        return $this->getThemeValueStylesString('color', ['torchlight.lineNumberColor', 'editorLineNumber.foreground']);
    }

    protected function getThemeValueStylesString(string $propertyName, array|string $themeProp, ?string $default = null): string
    {
        return $this->htmlGenerator->getThemeValueStylesString($propertyName, $themeProp, $default);
    }

    protected function getTokenStyles($token): array
    {
        return $this->htmlGenerator->getTokenStyles($token);
    }

    protected function makeToken(string $text, array $scopes): object
    {
        $token = new Token($scopes, $text, 0, 0);

        return $this->highlighter->highlight([[$token]])[0][0];
    }

    protected function getScopeSettings(array $scopes): array
    {
        /** @var HighlightedToken $highlightedToken */
        $highlightedToken = $this->makeToken('*', $scopes);

        return $highlightedToken->settings;
    }

    protected function getScopeStyles(array $scopes): string
    {
        return $this->htmlGenerator->getSettingsStyleString($this->getScopeSettings($scopes));
    }

    protected function renderText(string $text, array $scopes, array $classes = [], array $styles = []): string
    {
        return $this->htmlGenerator->buildToken(
            $this->makeToken($text, $scopes),
            $classes,
            $styles
        );
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        $this->highlighter = $highlighter;

        return $this;
    }
}
