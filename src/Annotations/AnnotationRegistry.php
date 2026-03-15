<?php

namespace Torchlight\Engine\Annotations;

use Phiki\Highlighting\Highlighter;
use Torchlight\Engine\Generators\ThemeStyleResolver;
use Torchlight\Engine\Options;

class AnnotationRegistry
{
    /**
     * @var AbstractAnnotation[]
     */
    protected array $annotations = [];

    /**
     * @var array<string, AbstractAnnotation>
     */
    protected array $prefixHandlers = [];

    /**
     * @var array<string, AbstractAnnotation>
     */
    protected array $nameToAnnotation = [];

    /**
     * @return string[]
     */
    protected function getPrefixesBySpecificity(): array
    {
        $prefixes = array_keys($this->prefixHandlers);

        usort($prefixes, function (string $left, string $right): int {
            $lengthCompare = mb_strlen($right) <=> mb_strlen($left);

            return $lengthCompare !== 0 ? $lengthCompare : strcmp($left, $right);
        });

        return $prefixes;
    }

    public function register(string $name, AbstractAnnotation $annotation): static
    {
        $this->annotations[$name] = $annotation;
        $this->nameToAnnotation[mb_strtolower($name)] = $annotation;

        return $this;
    }

    public function registerAnnotation(AbstractAnnotation $annotation): static
    {
        $name = $annotation::getName();

        $this->annotations[$name] = $annotation;
        $this->nameToAnnotation[mb_strtolower($name)] = $annotation;

        foreach ($annotation::getAliases() as $alias) {
            $this->nameToAnnotation[mb_strtolower($alias)] = $annotation;
        }

        $prefix = $annotation::getPrefix();
        if ($prefix !== null) {
            $this->prefixHandlers[$prefix] = $annotation;
        }

        return $this;
    }

    public function registerPrefixHandler(string $prefix, AbstractAnnotation $annotation): static
    {
        $this->prefixHandlers[$prefix] = $annotation;

        return $this;
    }

    public function unregister(string $name): static
    {
        $annotation = $this->annotations[$name] ?? null;

        if ($annotation === null) {
            return $this;
        }

        unset($this->annotations[$name]);

        $this->nameToAnnotation = array_filter(
            $this->nameToAnnotation,
            fn ($a) => $a !== $annotation
        );

        $this->prefixHandlers = array_filter(
            $this->prefixHandlers,
            fn ($a) => $a !== $annotation
        );

        return $this;
    }

    public function get(string $name): ?AbstractAnnotation
    {
        return $this->annotations[$name] ?? null;
    }

    public function resolve(string $nameOrPrefixedValue): ?AbstractAnnotation
    {
        foreach ($this->getPrefixesBySpecificity() as $prefix) {
            if (str_starts_with($nameOrPrefixedValue, $prefix)) {
                return $this->prefixHandlers[$prefix];
            }
        }

        return $this->nameToAnnotation[mb_strtolower($nameOrPrefixedValue)] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->annotations);
    }

    /**
     * @return string[]
     */
    public function getRegisteredNames(): array
    {
        return array_keys($this->nameToAnnotation);
    }

    /**
     * @return string[]
     */
    public function getRegisteredPrefixes(): array
    {
        return array_keys($this->prefixHandlers);
    }

    /**
     * @return AbstractAnnotation[]
     */
    public function all(): array
    {
        return $this->annotations;
    }

    /**
     * @return AbstractAnnotation[]
     */
    public function allIncludingPrefixHandlers(): array
    {
        return array_merge($this->annotations, $this->prefixHandlers);
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        foreach ($this->allIncludingPrefixHandlers() as $annotation) {
            $annotation->setHighlighter($highlighter);
        }

        return $this;
    }

    public function setTorchlightOptions(Options $options): static
    {
        foreach ($this->allIncludingPrefixHandlers() as $annotation) {
            $annotation->setTorchlightOptions($options);
        }

        return $this;
    }

    public function setThemeResolver(ThemeStyleResolver $resolver): static
    {
        foreach ($this->allIncludingPrefixHandlers() as $annotation) {
            $annotation->setThemeResolver($resolver);
        }

        return $this;
    }
}
