<?php

namespace Torchlight\Engine\Concerns;

use Phiki\Theme\ParsedTheme;
use Torchlight\Engine\Theme\Parser;

trait ManagesThemes
{
    protected function loadThemes(): static
    {
        $manifestJson = file_get_contents(__DIR__.'/../../resources/themes/themes.json');
        $decodedManifest = $manifestJson === false ? [] : json_decode($manifestJson, true);
        /** @var array<string, string> $manifest */
        $manifest = is_array($decodedManifest) ? $decodedManifest : [];
        $parser = new Parser;

        foreach ($manifest as $name => $path) {
            $themePath = __DIR__.'/../../resources/themes/normalized/'.$path;
            $themeJson = file_get_contents($themePath);
            $decodedTheme = $themeJson === false ? [] : json_decode($themeJson, true);
            /** @var array<string, mixed> $themeData */
            $themeData = is_array($decodedTheme) ? $decodedTheme : [];
            $parsedTheme = $parser->parse($themeData);

            $this->environment->themes->register($name, $parsedTheme);
        }

        return $this;
    }

    /**
     * @param  string  $name  The name to register the theme under
     * @param  string|array<string, mixed>|ParsedTheme  $theme  A file path, theme array, or ParsedTheme
     */
    public function registerTheme(string $name, string|array|ParsedTheme $theme): static
    {
        if (is_string($theme)) {
            $themeJson = file_get_contents($theme);
            $decodedTheme = $themeJson === false ? [] : json_decode($themeJson, true);
            /** @var array<string, mixed> $data */
            $data = is_array($decodedTheme) ? $decodedTheme : [];
            $parser = new Parser;
            $theme = $parser->parse($data);
        } elseif (is_array($theme)) {
            $parser = new Parser;
            $theme = $parser->parse($theme);
        }

        $this->environment->themes->register($name, $theme);

        return $this;
    }

    public function hasTheme(string $name): bool
    {
        return $this->environment->themes->has($name);
    }
}
