<?php

namespace Torchlight\Engine\Theme;

use Phiki\Support\Arr;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\TokenColor;
use Phiki\Theme\TokenSettings;

class Parser
{
    private function adjustScope(string $scope): string
    {
        // TODO: Remove this quick workaround to better support/have compatibility with themes.
        if (str_starts_with($scope, 'source.') && str_contains($scope, ' ')) {
            $parts = explode(' ', $scope, 2);
            $suffix = mb_substr($parts[0], 6);

            return $parts[1].$suffix;
        }

        return $scope;
    }

    public function parse(array $theme): ParsedTheme
    {
        $name = $theme['name'];
        $colors = $theme['colors'];

        $tokenColors = Arr::filterMap(
            array_merge_recursive(
                $theme['settings'] ?? [], // TODO: Review this.
                $theme['tokenColors'] ?? []
            ), function (array $tokenColor) {
                if (! isset($tokenColor['scope'])) {
                    return null;
                }

                $tmpScopes = Arr::wrap($tokenColor['scope']);
                $scopes = [];

                foreach ($tmpScopes as $scope) {
                    if (str_contains($scope, ',')) {
                        $subScopes = explode(',', $scope);

                        foreach ($subScopes as $subScope) {
                            $scopes[] = trim($this->adjustScope($subScope), " \n\r\t\v\0,");
                        }

                        continue;
                    }

                    $scopes[] = trim($this->adjustScope($scope), " \n\r\t\v\0,");
                }

                return new TokenColor($scopes, new TokenSettings(
                    $tokenColor['settings']['background'] ?? null,
                    $tokenColor['settings']['foreground'] ?? null,
                    $tokenColor['settings']['fontStyle'] ?? null,
                ));
            });

        return new ParsedTheme($name, $colors, $tokenColors);
    }
}
