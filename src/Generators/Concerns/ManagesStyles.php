<?php

namespace Torchlight\Engine\Generators\Concerns;

use Phiki\Theme\ParsedTheme;

trait ManagesStyles
{
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

    protected function getPhikiPropertyName(string $prefix, string $property): string
    {
        return "--phiki-{$prefix}-{$property}";
    }

    protected function getStyle(string $class): array
    {
        $styles = [];

        if (isset($this->styles[$class])) {
            foreach ($this->styles[$class] as $classProps) {
                [$themeProp, $propertyName, $defaultValue] = $classProps;

                /**
                 * @var string $id
                 * @var ParsedTheme $theme
                 */
                foreach ($this->themes as $id => $theme) {
                    $propName = $propertyName;

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

                    $themeValue ??= $defaultValue;

                    if ($id != $this->getDefaultThemeId()) {
                        $propName = $this->getPhikiPropertyName($id, $propertyName);
                    }

                    $styles[$propName] = $themeValue;
                }
            }
        }

        return $styles;
    }

    protected function getValueFromTheme(ParsedTheme $theme, string $propName): ?string
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

    protected function getLineStyles(array $classes): array
    {
        $styles = [];

        foreach ($classes as $class) {
            foreach ($this->getStyle($class) as $k => $v) {
                $styles[$k] = $v;
            }
        }

        return $styles;
    }

    protected function toAttributeString(array $attributes): string
    {
        $attributeParts = [];

        foreach ($attributes as $k => $v) {
            $attributeParts[] = "{$k}='{$v}'";
        }

        return implode(' ', $attributeParts);
    }

    protected function toStyleString(array $styles): string
    {
        $styleParts = [];

        foreach ($styles as $k => $v) {
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
}
