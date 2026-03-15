<?php

namespace Torchlight\Engine\Theme;

class FallbackColors
{
    /** @var array<string, array<string, string>>|null */
    protected static ?array $loadedSettings = null;

    protected static function loadSettings(): void
    {
        if (static::$loadedSettings !== null) {
            return;
        }

        $json = file_get_contents(__DIR__.'/../../resources/themes/settings.json');
        if ($json === false) {
            static::$loadedSettings = [];

            return;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            static::$loadedSettings = [];

            return;
        }

        $loadedSettings = [];

        foreach ($decoded as $theme => $themeSettings) {
            if (! is_string($theme) || ! is_array($themeSettings)) {
                continue;
            }

            foreach ($themeSettings as $settingName => $settingValue) {
                if (! is_string($settingName) || ! is_string($settingValue)) {
                    continue;
                }

                $loadedSettings[$theme][$settingName] = $settingValue;
            }
        }

        static::$loadedSettings = $loadedSettings;
    }

    public static function getDefaultForeground(string $theme): ?string
    {
        self::loadSettings();

        return self::$loadedSettings[$theme]['foreground'] ?? null;
    }
}
