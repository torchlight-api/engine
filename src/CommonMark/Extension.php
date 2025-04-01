<?php

namespace Torchlight\Engine\CommonMark;

use Closure;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\ExtensionInterface;
use Phiki\Theme\Theme;
use Torchlight\Engine\Engine;

class Extension implements ExtensionInterface
{
    protected static ?Closure $themeResolver = null;

    private Engine $engine;

    private CodeBlockRenderer $renderer;

    public static function setThemeResolver(Closure $resolver): void
    {
        static::$themeResolver = $resolver;
    }

    public function __construct(
        private Theme|array|string|null $theme = null,
        private bool $withGutter = true,
        array $preprocessors = [],
        Closure|array $renderCallbacks = [],
    ) {
        if (! $this->theme && static::$themeResolver) {
            $callback = static::$themeResolver;
            $this->theme = $callback();
        }

        $this->engine = new Engine;

        foreach ($preprocessors as $lang => $preprocessor) {
            $this->engine->registerPreprocessor(
                $preprocessor,
                is_string($lang) ? $lang : null
            );
        }

        $this->renderer = new CodeBlockRenderer($this->theme, $this->engine, $this->withGutter);

        if (! is_array($renderCallbacks)) {
            $renderCallbacks = [$renderCallbacks];
        }

        foreach ($renderCallbacks as $callback) {
            $this->addRenderCallback($callback);
        }
    }

    public function renderer(): CodeBlockRenderer
    {
        return $this->renderer;
    }

    public function addRenderCallback(Closure $callback): static
    {
        $this->renderer->addRenderCallback($callback);

        return $this;
    }

    public function clearRenderCallbacks(): static
    {
        $this->renderer->clearRenderCallbacks();

        return $this;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addRenderer(FencedCode::class, $this->renderer, 10);
    }
}
