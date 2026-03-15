<?php

namespace Torchlight\Engine\CommonMark;

use Closure;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\ExtensionInterface;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\Theme;
use Torchlight\Engine\Contracts\Preprocessor;
use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;
use Torchlight\Engine\Theme\Theme as TorchlightTheme;

class Extension implements ExtensionInterface
{
    /** @var Closure(): (Theme|TorchlightTheme|ParsedTheme|array<int|string, string|Theme|TorchlightTheme|ParsedTheme>|string|null)|null */
    protected static ?Closure $themeResolver = null;

    private readonly Engine $engine;

    private readonly CodeBlockRenderer $renderer;

    /** @param Closure(): (Theme|TorchlightTheme|ParsedTheme|array<int|string, string|Theme|TorchlightTheme|ParsedTheme>|string|null) $resolver */
    public static function setThemeResolver(Closure $resolver): void
    {
        static::$themeResolver = $resolver;
    }

    /**
     * @param  Theme|TorchlightTheme|ParsedTheme|array<int|string, string|Theme|TorchlightTheme|ParsedTheme>|string|null  $theme
     * @param  list<Closure|Preprocessor>|array<string, Closure|Preprocessor>  $preprocessors
     * @param  Closure(string): string|list<Closure(string): string>  $renderCallbacks
     * @param  array<string, string>  $replacers
     */
    public function __construct(
        private Theme|TorchlightTheme|ParsedTheme|array|string|null $theme = null,
        bool $withGutter = true,
        array $preprocessors = [],
        Closure|array $renderCallbacks = [],
        array $replacers = [],
    ) {
        if (! $this->theme && static::$themeResolver) {
            $callback = static::$themeResolver;
            $this->theme = $callback();
        }

        $this->engine = new Engine;
        $this->engine->setTorchlightOptions(
            Options::default()->mergeWith(['withGutter' => $withGutter])
        );

        foreach ($preprocessors as $lang => $preprocessor) {
            $this->engine->registerPreprocessor(
                $preprocessor,
                is_string($lang) ? $lang : null
            );
        }

        $this->engine->addReplacers($replacers);

        $this->renderer = new CodeBlockRenderer($this->theme ?? 'github-light', $this->engine);

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

    /** @param Closure(string): string $callback */
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
