<?php

namespace Torchlight\Engine\Theme;

use Phiki\Support\Arr;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\Scope;
use Phiki\Theme\TokenColor;
use Phiki\Theme\TokenSettings;

class Parser
{
    private function adjustScope(string $scope): string
    {
        if (str_starts_with($scope, 'source.') && str_contains($scope, ' ')) {
            $parts = explode(' ', $scope, 2);
            $suffix = mb_substr($parts[0], 6);

            return $parts[1].$suffix;
        }

        return $scope;
    }

    private function createScope(string $scopeStr): Scope
    {
        if (str_contains($scopeStr, ' ')) {
            return new Scope(array_map(trim(...), explode(' ', $scopeStr)));
        }

        return new Scope([$scopeStr]);
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public function parse(array $theme): ParsedTheme
    {
        $name = is_string($theme['name'] ?? null) ? $theme['name'] : 'theme';
        /** @var array<string, string> $colors */
        $colors = [];
        if (is_array($theme['colors'] ?? null)) {
            foreach ($theme['colors'] as $colorName => $colorValue) {
                if (! is_string($colorName) || ! is_string($colorValue)) {
                    continue;
                }

                $colors[$colorName] = $colorValue;
            }
        }
        $settings = is_array($theme['settings'] ?? null) ? $theme['settings'] : [];
        $tokenColorsConfig = is_array($theme['tokenColors'] ?? null) ? $theme['tokenColors'] : [];

        /** @var array<TokenColor> $tokenColors */
        $tokenColors = Arr::filterMap(
            array_merge_recursive(
                $settings,
                $tokenColorsConfig
            ), function (array $tokenColor) {
                if (! isset($tokenColor['scope'])) {
                    return null;
                }

                $tmpScopes = Arr::wrap($tokenColor['scope']);
                $scopes = [];

                foreach ($tmpScopes as $scope) {
                    if (! is_string($scope)) {
                        continue;
                    }

                    if (str_contains($scope, ',')) {
                        $subScopes = explode(',', $scope);

                        foreach ($subScopes as $subScope) {
                            $trimmed = trim($this->adjustScope($subScope), " \n\r\t\v\0,");
                            if ($trimmed !== '') {
                                $scopes[] = $this->createScope($trimmed);
                            }
                        }

                        continue;
                    }

                    $trimmed = trim($this->adjustScope($scope), " \n\r\t\v\0,");
                    if ($trimmed !== '') {
                        $scopes[] = $this->createScope($trimmed);
                    }
                }

                $settings = is_array($tokenColor['settings'] ?? null) ? $tokenColor['settings'] : [];

                return new TokenColor($scopes, new TokenSettings(
                    is_string($settings['background'] ?? null) ? $settings['background'] : null,
                    is_string($settings['foreground'] ?? null) ? $settings['foreground'] : null,
                    is_string($settings['fontStyle'] ?? null) ? $settings['fontStyle'] : null,
                ));
            });

        return new ParsedTheme($name, $colors, $tokenColors);
    }
}
