<?php

namespace Torchlight\Engine\Concerns;

trait ManagesThemes
{
    protected function loadThemes(): static
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/../../resources/themes/themes.json'), true);

        foreach ($manifest as $name => $path) {
            $this->environment->getThemeRepository()->register(
                $name,
                __DIR__.'/../../resources/themes/normalized/'.$path
            );
        }

        return $this;
    }
}
