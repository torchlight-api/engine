<?php

namespace Torchlight\Engine\Theme;

use Phiki\Exceptions\UnrecognisedThemeException;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\ThemeRepository as BaseRepository;

class ThemeRepository extends BaseRepository
{
    public function get(string $name): ParsedTheme
    {
        if (! $this->has($name)) {
            throw UnrecognisedThemeException::make($name);
        }

        $theme = $this->themes[$name];

        if ($theme instanceof ParsedTheme) {
            return $theme;
        }

        $parser = new Parser;

        return $this->themes[$name] = $parser->parse(json_decode(file_get_contents($theme), true));
    }

    public function getThemesAndSettings(): array
    {
        $themes = [];

        foreach (array_keys($this->themes) as $id) {
            $theme = $this->get($id);

            $themes[] = [
                'name' => $theme->name,
                'background' => $theme->base()->background,
                'foreground' => $theme->base()->foreground,
            ];
        }

        return $themes;
    }
}
