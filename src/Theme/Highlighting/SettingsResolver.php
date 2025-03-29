<?php

namespace Torchlight\Engine\Theme\Highlighting;

use Phiki\Theme\ParsedTheme;
use Phiki\Theme\TokenSettings;

class SettingsResolver
{
    protected array $indexedThemes = [];

    protected function indexTheme(ParsedTheme $theme): void
    {
        $tokenColors = [];

        foreach ($theme->tokenColors as $tokenColor) {
            $settings = $tokenColor->settings;

            foreach ($tokenColor->scopes as $scope) {
                $parts = explode('.', $scope);
                $current = &$tokenColors;

                foreach ($parts as $part) {
                    if (! isset($current[$part])) {
                        $current[$part] = [];
                    }

                    $current = &$current[$part];
                }

                $current['*'] = $settings;
            }
        }

        $this->indexedThemes[$theme->name] = $tokenColors;
    }

    protected function resolveStyles(array $settings): ?TokenSettings
    {
        if (count($settings) === 1) {
            return $settings[0];
        }

        $currentBackground = null;
        $currentForeground = null;
        $currentFontStyle = null;

        foreach ($settings as $setting) {
            if ($setting->background !== null) {
                $currentBackground = $setting->background;
            }

            if ($setting->foreground !== null) {
                $currentForeground = $setting->foreground;
            }

            if ($setting->fontStyle !== null) {
                $currentFontStyle = $setting->fontStyle;
            }
        }

        if (
            $currentBackground === null &&
            $currentFontStyle === null &&
            $currentForeground === null
        ) {
            return null;
        }

        return new TokenSettings(
            $currentBackground,
            $currentForeground,
            $currentFontStyle
        );
    }

    public function resolve(ParsedTheme $theme, $scope)
    {
        // TODO: Review all of this logic, make less awful, and improve compatibility with all themes.
        if (! isset($this->indexedThemes[$theme->name])) {
            $this->indexTheme($theme);
        }

        $scopeParts = explode('.', $scope);
        $scopeLevels = [];
        $partLen = count($scopeParts);

        for ($i = $partLen; $i > 0; $i--) {
            $scopeLevels[] = implode('.', array_slice($scopeParts, 0, $i));
        }

        $current = $this->indexedThemes[$theme->name];
        $currentBackground = null;
        $currentForeground = null;
        $currentFontStyle = null;

        $settings = null;

        foreach ($scopeLevels as $level) {
            $parts = explode('.', $level);

            foreach ($parts as $part) {
                // Can't find the right part here, break.
                if (! isset($current[$part])) {
                    break;
                }

                $current = $current[$part];

                if (isset($current['*'])) {
                    /** @var TokenSettings $settings */
                    $settings = $current['*'];

                    if ($settings->background !== null) {
                        $currentBackground = $settings->background;
                    }

                    if ($settings->foreground !== null) {
                        $currentForeground = $settings->foreground;
                    }

                    if ($settings->fontStyle !== null) {
                        $currentFontStyle = $settings->fontStyle;
                    }
                }
            }
        }

        if (! $settings) {
            return null;
        }

        return new TokenSettings(
            $currentBackground,
            $currentForeground,
            $currentFontStyle
        );
    }
}
