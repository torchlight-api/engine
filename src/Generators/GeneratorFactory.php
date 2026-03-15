<?php

namespace Torchlight\Engine\Generators;

use Phiki\Highlighting\Highlighter;
use Phiki\Theme\ParsedTheme;
use Torchlight\Engine\Annotations\AnnotationEngine;
use Torchlight\Engine\Contracts\BlockDecorator;
use Torchlight\Engine\Contracts\TokenTransformer;
use Torchlight\Engine\Options;

class GeneratorFactory
{
    /**
     * @param  list<callable(): TokenTransformer>  $tokenTransformerFactories
     * @param  list<callable(): BlockDecorator>  $blockDecoratorFactories
     */
    public function __construct(
        protected array $tokenTransformerFactories = [],
        protected array $blockDecoratorFactories = [],
    ) {}

    /** @param list<callable(): TokenTransformer> $factories */
    public function setTokenTransformerFactories(array $factories): static
    {
        $this->tokenTransformerFactories = $factories;

        return $this;
    }

    /** @param list<callable(): BlockDecorator> $factories */
    public function setBlockDecoratorFactories(array $factories): static
    {
        $this->blockDecoratorFactories = $factories;

        return $this;
    }

    /**
     * @param  array<string, ParsedTheme>  $themes
     */
    public function create(
        ?string $grammarName,
        array $themes,
        Highlighter $highlighter,
        AnnotationEngine $annotationProcessor,
        Options $options,
        string $cleanedText,
        string $languageVanityLabel,
    ): HtmlGenerator {
        $generator = new HtmlGenerator(
            $grammarName,
            $themes,
        );

        $themeResolver = new ThemeStyleResolver($themes, $highlighter, $options);

        $generator
            ->setHighlighter($highlighter)
            ->setThemeResolver($themeResolver);

        $generationOptions = $annotationProcessor->getGenerationOptions();

        $gutterServices = new GutterServices($generator, $themeResolver, $highlighter);

        $generationOptions->gutterServices = $gutterServices;

        foreach ($generationOptions->gutters as $gutter) {
            $gutter->setServices($gutterServices);
            $gutter->setGenerationOptions($generationOptions);
        }

        $annotationProcessor->getRegistry()
            ->setThemeResolver($themeResolver);

        $generator
            ->setGenerationOptions($generationOptions)
            ->setCleanedText($cleanedText)
            ->setLanguageVanityLabel($languageVanityLabel);

        foreach ($this->tokenTransformerFactories as $factory) {
            $generator->registerTokenTransformer($factory());
        }

        foreach ($this->blockDecoratorFactories as $factory) {
            $generator->registerBlockDecorator($factory());
        }

        return $generator;
    }
}
