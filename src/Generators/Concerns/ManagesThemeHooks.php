<?php

namespace Torchlight\Engine\Generators\Concerns;

use Closure;
use Torchlight\Engine\Options;
use Torchlight\Engine\Theme\Hooks\Fortnite;
use Torchlight\Engine\Theme\Hooks\Moonlight;
use Torchlight\Engine\Theme\Hooks\Synthwave84;

trait ManagesThemeHooks
{
    /** @var array<string, array<string, list<Closure(string, Options, string, string): string>>> */
    protected array $themeHooks = [];

    protected function runAfterRenderHooks(string $theme, string $output, Options $torchlightOptions, string $propertyPrefix, string $themeName): string
    {
        foreach ($this->getThemeHooks($theme, 'afterRender') as $callback) {
            $output = $callback($output, $torchlightOptions, $propertyPrefix, $themeName);
        }

        return $output;
    }

    public function registerAfterRenderHook(string $theme, Closure $callback): static
    {
        return $this->registerThemeHook($theme, 'afterRender', $callback);
    }

    public function registerThemeHook(string $theme, string $hook, Closure $callback): static
    {
        if (! array_key_exists($theme, $this->themeHooks)) {
            $this->themeHooks[$theme] = [];
        }

        if (! array_key_exists($hook, $this->themeHooks[$theme])) {
            $this->themeHooks[$theme][$hook] = [];
        }

        $this->themeHooks[$theme][$hook][] = $callback;

        return $this;
    }

    /**
     * @return list<Closure(string, Options, string, string): string>
     */
    public function getThemeHooks(string $theme, string $hook): array
    {
        return $this->themeHooks[$theme][$hook] ?? [];
    }

    protected function loadDefaultThemeHooks(): static
    {
        return $this
            ->registerAfterRenderHook('moonlight-ii', fn (string $html, Options $options, string $propertyPrefix, string $themeId): string => Moonlight::replaceColors($html, $options, $propertyPrefix, $themeId))
            ->registerAfterRenderHook('fortnite', fn (string $html, Options $options, string $propertyPrefix, string $themeId): string => Fortnite::replaceColors($html, $options, $propertyPrefix, $themeId))
            ->registerAfterRenderHook('synthwave-84', fn (string $html, Options $options, string $propertyPrefix, string $themeId): string => Synthwave84::replaceColors($html, $options, $propertyPrefix, $themeId));
    }
}
