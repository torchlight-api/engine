<?php

namespace Torchlight\Engine\Theme;

// TODO: Work to not need this anymore :)
class FallbackColors
{
    protected static ?array $loadedSettings = null;

    protected static function loadSettings(): void
    {
        if (static::$loadedSettings !== null) {
            return;
        }

        static::$loadedSettings = json_decode(
            file_get_contents(__DIR__.'/../../resources/themes/settings.json'),
            true
        );
    }

    public static function getDefaultForeground(string $theme): ?string
    {
        self::loadSettings();

        return self::$loadedSettings[$theme]['foreground'] ?? null;
    }
}
