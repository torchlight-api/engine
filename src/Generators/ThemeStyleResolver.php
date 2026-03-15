<?php

namespace Torchlight\Engine\Generators;

use LogicException;
use Phiki\Highlighting\Highlighter;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\TokenSettings;
use Phiki\Token\HighlightedToken;
use Phiki\Token\Token;
use Torchlight\Engine\Options;
use Torchlight\Engine\Theme\FallbackColors;

class ThemeStyleResolver
{
    /** @var array<string, list<array{0:list<string>, 1:string, 2:string|null}>> */
    protected array $styles = [
        'line-highlight' => [
            [
                ['editor.lineHighlightBackground', 'editor.selectionHighlightBackground', 'theme::background'],
                'background',
                '#00000050',
            ],
        ],
        'line-add' => [
            [
                ['torchlight.markupInsertedBackground', 'diffEditor.insertedTextBackground'],
                'background',
                '#89DDFF20',
            ],
        ],
        'line-remove' => [
            [
                ['torchlight.markupDeletedBackground', 'diffEditor.removedTextBackground'],
                'background',
                '#ff9cac20',
            ],
        ],
    ];

    /**
     * @param  array<string, ParsedTheme>  $themes
     */
    public function __construct(
        /** @var array<string, ParsedTheme> */
        protected array $themes,
        protected ?Highlighter $highlighter = null,
        protected ?Options $options = null,
    ) {}

    public function setHighlighter(Highlighter $highlighter): static
    {
        $this->highlighter = $highlighter;

        return $this;
    }

    public function setOptions(Options $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  list<string>|string  $themeProp
     * @return array<string, string|null>
     */
    public function getThemeValueStyles(string $propertyName, array|string $themeProp, ?string $default = null): array
    {
        return $this->resolveThemeProperty($propertyName, $themeProp, $default);
    }

    /** @param list<string>|string $themeProp */
    public function getThemeValueStylesString(string $propertyName, array|string $themeProp, ?string $default = null): string
    {
        return $this->toStyleString($this->getThemeValueStyles($propertyName, $themeProp, $default));
    }

    /**
     * @param  list<string>|string  $themeProp
     * @return array<string, string|null>
     */
    private function resolveThemeProperty(string $propertyName, array|string $themeProp, ?string $default = null): array
    {
        $styles = [];

        foreach ($this->themes as $id => $theme) {
            $propName = $propertyName;
            $themeValue = null;

            if (is_array($themeProp)) {
                foreach ($themeProp as $tryPropName) {
                    $themeValue = $this->getValueFromTheme($theme, $tryPropName);

                    if ($themeValue) {
                        break;
                    }
                }
            } else {
                $themeValue = $this->getValueFromTheme($theme, $themeProp);
            }

            $themeValue ??= $default;

            if ($id != $this->getDefaultThemeId()) {
                $propName = $this->getPhikiPropertyName($id, $propertyName);
            }

            $styles[$propName] = $themeValue;
        }

        return $styles;
    }

    public function getValueFromTheme(ParsedTheme $theme, string $propName): ?string
    {
        if ($propName === 'theme::background') {
            return $theme->base()->background;
        } elseif ($propName === 'theme::foreground') {
            return $theme->base()->foreground;
        } elseif ($propName === 'theme::fontStyle') {
            return $theme->base()->fontStyle;
        }

        return $theme->colors[$propName] ?? null;
    }

    /** @return list<string> */
    public function getTokenStyles(HighlightedToken $token): array
    {
        return $this->getSettingsStylesArray(
            $this->adjustTokenStyles($token)->settings
        );
    }

    /**
     * @param  array<string, TokenSettings>  $tokenSettings
     * @return list<string>
     */
    public function getSettingsStylesArray(array $tokenSettings): array
    {
        $defaultThemeId = $this->getDefaultThemeId();

        $tokenStyles = [];

        $defaultSettings = $tokenSettings[$defaultThemeId] ?? null;
        if ($defaultSettings !== null) {
            $tokenStyles[] = $defaultSettings->toStyleString();
        }

        foreach ($tokenSettings as $id => $settings) {
            if ($id !== $defaultThemeId) {
                $tokenStyles[] = $settings->toCssVarString($id);
            }
        }

        return $tokenStyles;
    }

    /**
     * @param  array<string, TokenSettings>  $tokenSettings
     */
    public function getSettingsStyleString(array $tokenSettings): string
    {
        return $this->toStyleString($this->getSettingsStylesArray($tokenSettings));
    }

    protected function adjustTokenStyles(HighlightedToken $token): HighlightedToken
    {
        /** @var array<string, TokenSettings> $newSettings */
        $newSettings = [];
        /** @var array<string, TokenSettings> $currentSettings */
        $currentSettings = $token->settings;

        foreach ($this->themes as $id => $theme) {
            if (array_key_exists($id, $currentSettings)) {
                continue;
            }

            $currentSettings[$id] = new TokenSettings(null, null, null);
        }

        foreach ($currentSettings as $id => $settings) {
            $foreground = $settings->foreground;
            $theme = $this->themes[$id] ?? null;
            $themeName = $theme === null ? '' : $theme->name;

            if (mb_strlen(trim($token->token->text)) > 0) {
                if ($foreground === null) {
                    $foreground = FallbackColors::getDefaultForeground($themeName);
                }
            }

            $outputFontStyles = $this->options === null ? true : $this->options->outputFontStyles;

            $newSettings[$id] = new TokenSettings(
                null,
                $foreground,
                $outputFontStyles ? $settings->fontStyle : null
            );
        }

        return new HighlightedToken(
            $token->token,
            $newSettings
        );
    }

    /**
     * @param  list<string>  $scopes
     * @return array<string, TokenSettings>
     */
    public function getScopeSettings(array $scopes): array
    {
        $highlightedToken = $this->makeToken('*', $scopes);

        return $highlightedToken->settings;
    }

    /** @param list<string> $scopes */
    public function getScopeStyles(array $scopes): string
    {
        return $this->getSettingsStyleString($this->getScopeSettings($scopes));
    }

    /** @param list<string> $scopes */
    public function makeToken(string $text, array $scopes): HighlightedToken
    {
        $token = new Token($scopes, $text, 0, 0);
        /** @var array<int, array<int, HighlightedToken>> $highlightedLines */
        $highlightedLines = $this->highlighter()->highlight([[$token]]);
        if (! isset($highlightedLines[0][0]) || ! $highlightedLines[0][0] instanceof HighlightedToken) {
            throw new LogicException('Unable to generate highlighted token.');
        }

        return $highlightedLines[0][0];
    }

    /** @return array<string, string|null> */
    public function getStyle(string $class): array
    {
        $styles = [];

        if (isset($this->styles[$class])) {
            foreach ($this->styles[$class] as $classProps) {
                [$themeProp, $propertyName, $defaultValue] = $classProps;

                foreach ($this->resolveThemeProperty($propertyName, $themeProp, $defaultValue) as $k => $v) {
                    $styles[$k] = $v;
                }
            }
        }

        return $styles;
    }

    /**
     * @param  list<string>  $classes
     * @return array<string, string|null>
     */
    public function getLineStyles(array $classes): array
    {
        $styles = [];

        foreach ($classes as $class) {
            foreach ($this->getStyle($class) as $k => $v) {
                $styles[$k] = $v;
            }
        }

        return $styles;
    }

    /**
     * @param  list<string>|string  $themeProp
     */
    public function registerLineStyle(string $class, array|string $themeProp, string $cssProperty, ?string $default = null): static
    {
        $this->styles[$class] ??= [];

        $themeProperties = is_array($themeProp) ? array_values($themeProp) : [$themeProp];

        $this->styles[$class][] = [
            $themeProperties,
            $cssProperty,
            $default,
        ];

        return $this;
    }

    public function getDefaultTheme(): ParsedTheme
    {
        $firstKey = array_key_first($this->themes);

        if ($firstKey === null) {
            throw new LogicException('At least one theme is required.');
        }

        /** @var ParsedTheme $theme */
        $theme = $this->themes[$firstKey];

        return $theme;
    }

    public function getDefaultThemeId(): string
    {
        $firstKey = array_key_first($this->themes);

        if (! is_string($firstKey)) {
            throw new LogicException('At least one theme id is required.');
        }

        return $firstKey;
    }

    /** @return array<string, ParsedTheme> */
    public function getThemes(): array
    {
        return $this->themes;
    }

    public function getPhikiPropertyName(string $prefix, string $property): string
    {
        return "--phiki-{$prefix}-{$property}";
    }

    /**
     * @param  array<string, string>  $attributes
     */
    public static function toAttributeString(array $attributes): string
    {
        $attributeParts = [];

        foreach ($attributes as $k => $v) {
            $attributeParts[] = "{$k}='{$v}'";
        }

        return implode(' ', $attributeParts);
    }

    /**
     * @param  array<int|string, string|null>  $styles
     */
    public function toStyleString(array $styles): string
    {
        $styleParts = [];

        foreach ($styles as $k => $v) {
            if ($v === null) {
                continue;
            }

            if (! is_string($k)) {
                $styleParts[] = $v;

                continue;
            }

            $styleParts[] = "{$k}: {$v}";
        }

        if (count($styleParts) === 0) {
            return '';
        }

        return implode(';', $styleParts);
    }

    private function highlighter(): Highlighter
    {
        if ($this->highlighter === null) {
            throw new LogicException('Theme highlighter has not been configured.');
        }

        return $this->highlighter;
    }
}
