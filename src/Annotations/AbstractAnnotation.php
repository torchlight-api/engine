<?php

namespace Torchlight\Engine\Annotations;

use Closure;
use Phiki\Highlighting\Highlighter;
use ReflectionClass;
use Torchlight\Engine\Annotations\Contracts\AnnotationDescriptor;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Ranges\AnnotationRange;
use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Generators\Gutters\AbstractGutter;
use Torchlight\Engine\Generators\Gutters\CollapseGutter;
use Torchlight\Engine\Generators\Gutters\CustomContentGutter;
use Torchlight\Engine\Generators\Gutters\DiffGutter;
use Torchlight\Engine\Generators\Gutters\LineNumbersGutter;
use Torchlight\Engine\Generators\ThemeStyleResolver;
use Torchlight\Engine\Options;

abstract class AbstractAnnotation implements AnnotationDescriptor
{
    protected ?ThemeStyleResolver $themeResolver = null;

    protected Options $options;

    protected ?ImpactedRange $range = null;

    protected ?ParsedAnnotation $parsedAnnotation = null;

    public static string $name = '';

    /** @var list<string> */
    public static array $aliases = [];

    public static ?string $prefix = null;

    /**
     * @var array<string, Annotation|false>
     */
    private static array $attributeCache = [];

    protected static function resolveAttribute(): ?Annotation
    {
        $class = static::class;

        if (! array_key_exists($class, self::$attributeCache)) {
            $ref = new ReflectionClass($class);
            $attrs = $ref->getAttributes(Annotation::class);
            self::$attributeCache[$class] = ! empty($attrs) ? $attrs[0]->newInstance() : false;
        }

        $cached = self::$attributeCache[$class];

        return $cached === false ? null : $cached;
    }

    public static function getName(): string
    {
        $attribute = static::resolveAttribute();

        return $attribute !== null ? $attribute->name : static::$name;
    }

    /** @return list<string> */
    public static function getAliases(): array
    {
        $attribute = static::resolveAttribute();

        return $attribute !== null ? $attribute->aliases : static::$aliases;
    }

    public static function getPrefix(): ?string
    {
        $attr = static::resolveAttribute();

        return $attr !== null ? $attr->prefix : static::$prefix;
    }

    public static function acceptsMethodArgs(): bool
    {
        $attribute = static::resolveAttribute();

        return $attribute !== null ? $attribute->methodArgs : true;
    }

    public static function acceptsOptions(): bool
    {
        $attribute = static::resolveAttribute();

        return $attribute !== null ? $attribute->options : true;
    }

    public static function supportsCharacterRanges(): bool
    {
        $attribute = static::resolveAttribute();

        return $attribute !== null && $attribute->charRanges;
    }

    public static function supportsLineRanges(): bool
    {
        $attribute = static::resolveAttribute();

        return $attribute !== null ? $attribute->lineRanges : true;
    }

    public function __construct(
        protected AnnotationEngine $annotationEngine,
    ) {
        $this->options = Options::default();
    }

    public function reset(): void
    {
        $this->parsedAnnotation = null;
        $this->range = null;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function setThemeResolver(ThemeStyleResolver $resolver): static
    {
        $this->themeResolver = $resolver;

        return $this;
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        if ($this->themeResolver !== null) {
            $this->themeResolver->setHighlighter($highlighter);
        }

        return $this;
    }

    protected function getLineNumberColorStyles(): string
    {
        return $this->themeResolver()->getThemeValueStylesString(
            'color',
            ['torchlight.lineNumberColor', 'editorLineNumber.foreground']
        );
    }

    protected function getThemeStyle(string $key): string
    {
        if ($this->themeResolver === null) {
            return '';
        }

        return $this->themeResolver()->toStyleString(
            $this->themeResolver()->getStyle($key)
        );
    }

    /**
     * @param  string|list<string>  $class
     */
    protected function addBlockClass(array|string $class): static
    {
        if (! is_array($class)) {
            $class = [$class];
        }

        foreach ($class as $className) {
            $this->annotationEngine->addBlockClass($className);
        }

        return $this;
    }

    protected function eachLine(callable|Closure $callback): static
    {
        $range = $this->activeRange();

        for ($i = $range->startLine; $i <= $range->endLine; $i++) {
            call_user_func_array($callback, [$i]);
        }

        return $this;
    }

    /**
     * @param  string|list<string>  $scope
     */
    protected function addLineScope(array|string $scope): static
    {
        if (! is_array($scope)) {
            $scope = [$scope];
        }

        return $this->eachLine(function (int $i) use ($scope): void {
            $this->annotationEngine->addScopeToLine($i, $scope);
        });
    }

    protected function markLinesHighlighted(): static
    {
        return $this->eachLine(function (int $i): void {
            $this->lineNumbersGutter()->markLineHighlighted($i);
        });
    }

    protected function addClassToCharacterRange(string $class): static
    {
        $this->annotationEngine->addClassToCharacterRange(
            $this->activeRange()->startLine,
            intval($this->annotationRange()->start),
            intval($this->annotationRange()->end),
            $class
        );

        return $this;
    }

    protected function addIdToCharacterRange(string $id): static
    {
        $this->annotationEngine->addIdToCharacterRange(
            $this->activeRange()->startLine,
            intval($this->annotationRange()->start),
            intval($this->annotationRange()->end),
            $id
        );

        return $this;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function addAttributesToCharacterRange(array $attributes): static
    {
        $this->annotationEngine->addAttributesToCharacterRange(
            $this->activeRange()->startLine,
            intval($this->annotationRange()->start),
            intval($this->annotationRange()->end),
            $attributes
        );

        return $this;
    }

    protected function addStyledCharacterRange(string $class, string $styleKey): static
    {
        $styles = $this->themeResolver()->getStyle($styleKey);
        $styleString = $this->themeResolver()->toStyleString($styles);

        $attributes = ['class' => $class];

        if ($styleString !== '') {
            $attributes['style'] = $styleString;
        }

        return $this->addAttributesToCharacterRange($attributes);
    }

    protected function removeLine(int $line): static
    {
        $this->annotationEngine->removeLine($line);

        return $this;
    }

    /**
     * @param  string|list<string>  $class
     */
    protected function addLineClass(array|string $class): static
    {
        if (! is_array($class)) {
            $class = [$class];
        }

        return $this->eachLine(function (int $i) use ($class): void {
            foreach ($class as $className) {
                $this->annotationEngine->addLineClass($i, $className);
            }
        });
    }

    protected function addLineAttribute(string $name, string $value): static
    {
        return $this->eachLine(function (int $i) use ($name, $value): void {
            $this->annotationEngine->addAttributeToLine($i, $name, $value);
        });
    }

    /**
     * @param  list<string>  $scopes
     */
    protected function replaceLineMarker(string $marker, array $scopes = []): static
    {
        return $this->eachLine(function (int $i) use ($marker, $scopes): void {
            $this->lineNumbersGutter()->replaceLineMarker($i, [$marker, $scopes]);
        });
    }

    /**
     * @param  list<string>  $scopes
     */
    protected function setLineScopes(array $scopes): static
    {
        return $this->eachLine(function (int $i) use ($scopes): void {
            $this->lineNumbersGutter()->setLineScopes($i, $scopes);
        });
    }

    protected function setDiffLineMarker(string $marker): static
    {
        return $this->eachLine(function (int $i) use ($marker): void {
            $this->diffGutter()->setLineMarker($i, $marker);
        });
    }

    protected function setGutterLineContent(string $content): static
    {
        return $this->eachLine(function (int $i) use ($content): void {
            $this->customContentGutter()->setLineContent($i, $content);
        });
    }

    protected function prependLine(int $line, string $content): static
    {
        $this->annotationEngine->prependLine($line, $content);

        return $this;
    }

    protected function appendLine(int $line, string $content): static
    {
        $this->annotationEngine->appendLine($line, $content);

        return $this;
    }

    protected function reindexLine(int $originalLine, ?int $newLine, ?int $relativeOffset = null): static
    {
        $this->annotationEngine->reindexLine($originalLine, $newLine, $relativeOffset);

        return $this;
    }

    protected function forceDisplayLine(int $originalLine, int $newLine): static
    {
        $this->annotationEngine->lineNumbersGutter()->forceLineDisplay($originalLine, $newLine);

        return $this;
    }

    protected function surroundStartLine(string $prefix, string $suffix): static
    {
        return $this->prependLine($this->activeRange()->startLine, $prefix)
            ->appendLine($this->activeRange()->startLine, $suffix);
    }

    protected function surroundEndLine(string $prefix, string $suffix): static
    {
        return $this->prependLine($this->activeRange()->endLine, $prefix)
            ->appendLine($this->activeRange()->endLine, $suffix);
    }

    protected function surroundLine(int $line, string $prefix, string $suffix): static
    {
        $this->annotationEngine->surroundLine($line, $prefix, $suffix);

        return $this;
    }

    protected function surroundRange(string $prefix, string $suffix): static
    {
        $this->annotationEngine->surroundRange($this->activeRange(), $prefix, $suffix);

        return $this;
    }

    protected function modifyRangeTokens(callable|Closure $callback): static
    {
        $this->eachLine(function (int $line) use ($callback): void {
            $this->annotationEngine->modifyLineTokens($line, $callback);
        });

        return $this;
    }

    protected function modifyRangeContents(callable|Closure $callback): static
    {
        $this->eachLine(function (int $line) use ($callback): void {
            $this->modifyLineContents($line, $callback);
        });

        return $this;
    }

    public function safeReplace(string $search, string $replace, string $subject): string
    {
        return $this->annotationEngine->safeReplace($search, $replace, $subject);
    }

    protected function modifyLineContents(int $line, callable|Closure $callback): static
    {
        $this->annotationEngine->modifyLineContents($line, $callback);

        return $this;
    }

    protected function modifyStartLineContents(callable|Closure $callback): static
    {
        return $this->modifyLineContents($this->activeRange()->startLine, $callback);
    }

    public function setParsedAnnotation(ParsedAnnotation $annotation): static
    {
        $this->parsedAnnotation = $annotation;

        return $this;
    }

    public function setActiveRange(ImpactedRange $range): static
    {
        $this->range = $range;

        return $this;
    }

    protected function isCharacterRange(): bool
    {
        if ($this->parsedAnnotation === null || $this->parsedAnnotation->range === null) {
            return false;
        }

        return $this->parsedAnnotation->range->type == RangeType::Character;
    }

    protected function themeResolver(): ThemeStyleResolver
    {
        if ($this->themeResolver === null) {
            throw new \LogicException('Theme resolver has not been configured.');
        }

        return $this->themeResolver;
    }

    protected function activeRange(): ImpactedRange
    {
        if ($this->range === null) {
            throw new \LogicException('Active annotation range has not been configured.');
        }

        return $this->range;
    }

    protected function activeAnnotation(): ParsedAnnotation
    {
        if ($this->parsedAnnotation === null) {
            throw new \LogicException('Parsed annotation has not been configured.');
        }

        return $this->parsedAnnotation;
    }

    protected function annotationRange(): AnnotationRange
    {
        $range = $this->activeAnnotation()->range;

        if ($range === null) {
            throw new \LogicException('Annotation range is required for this operation.');
        }

        return $range;
    }

    protected function lineNumbersGutter(): LineNumbersGutter
    {
        return $this->annotationEngine->lineNumbersGutter();
    }

    protected function diffGutter(): DiffGutter
    {
        return $this->annotationEngine->diffGutter();
    }

    protected function collapseGutter(): CollapseGutter
    {
        return $this->annotationEngine->collapseGutter();
    }

    protected function customContentGutter(): CustomContentGutter
    {
        return $this->annotationEngine->customContentGutter();
    }

    protected function getGutter(string $name): ?AbstractGutter
    {
        return $this->annotationEngine->getGenerationOptions()->gutters[$name] ?? null;
    }

    protected function onLine(ParsedAnnotation $annotation): void {}

    protected function onCharacterRange(ParsedAnnotation $annotation): void
    {
        $this->onLine($annotation);
    }

    public function process(ParsedAnnotation $annotation): void
    {
        if ($this->isCharacterRange()) {
            $this->onCharacterRange($annotation);
        } else {
            $this->onLine($annotation);
        }
    }

    public function beforeProcess(): void {}

    public function afterProcess(): void {}
}
