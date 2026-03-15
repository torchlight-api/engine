<?php

namespace Torchlight\Engine;

use Closure;

class Options
{
    protected static ?Options $default = null;

    protected static ?Closure $defaultOptionsBuilder = null;

    private const LINE_NUMBERS_ENABLED = true;

    private const LINE_NUMBERS_START = 1;

    private const LINE_NUMBERS_STYLE = 'text-align: right; -webkit-user-select: none; user-select: none;';

    private const LINE_NUMBER_AND_DIFF_INDICATOR_RIGHT_PADDING = 0;

    private const DIFF_INDICATORS_ENABLED = true;

    private const DIFF_INDICATORS_IN_PLACE_OF_NUMBERS = true;

    private const DIFF_PRESERVE_SYNTAX_COLORS = false;

    private const SHOW_SUMMARY_CARETS = true;

    private const SUMMARY_COLLAPSED_INDICATOR = '...';

    private const ANNOTATIONS_ENABLED = true;

    private const FILE_STYLE = 'ascii';

    private const COPYABLE = false;

    private const ARIA_ENABLED = false;

    private const WITH_GUTTER = true;

    /**
     * @param  list<array{0:int, 1:int}>  $highlightLines
     * @param  list<array{0:int, 1:int}>  $addLines
     * @param  list<array{0:int, 1:int}>  $removeLines
     * @param  list<array{0:int, 1:int}>  $focusLines
     * @param  list<array{0:int, 1:int}>  $autolinkLines
     * @param  list<array{0:int, 1:int}>  $monoLines
     * @param  list<array{0:int, 1:int}>  $hideLines
     * @param  array<int|string, string>  $themes
     * @param  list<int>  $columnGuides
     */
    public function __construct(
        public bool $lineNumbersEnabled = self::LINE_NUMBERS_ENABLED,
        public int $lineNumbersStart = self::LINE_NUMBERS_START,
        public string $lineNumbersStyle = self::LINE_NUMBERS_STYLE,
        public int $lineNumberAndDiffIndicatorRightPadding = self::LINE_NUMBER_AND_DIFF_INDICATOR_RIGHT_PADDING,
        public bool $diffIndicatorsEnabled = self::DIFF_INDICATORS_ENABLED,
        public bool $diffIndicatorsInPlaceOfNumbers = self::DIFF_INDICATORS_IN_PLACE_OF_NUMBERS,
        public bool $diffPreserveSyntaxColors = self::DIFF_PRESERVE_SYNTAX_COLORS,
        public bool $showSummaryCarets = self::SHOW_SUMMARY_CARETS,
        public string $summaryCollapsedIndicator = self::SUMMARY_COLLAPSED_INDICATOR,
        public bool $annotationsEnabled = self::ANNOTATIONS_ENABLED,
        public string $fileStyle = self::FILE_STYLE,
        public bool $copyable = self::COPYABLE,
        public bool $ariaEnabled = self::ARIA_ENABLED,
        public bool $withGutter = self::WITH_GUTTER,
        public array $highlightLines = [],
        public array $addLines = [],
        public array $removeLines = [],
        public array $focusLines = [],
        public array $autolinkLines = [],
        public array $monoLines = [],
        public array $hideLines = [],
        public array $themes = [],
        public string $classes = '',
        public bool $fallbackOnUnknownGrammar = true,
        public bool $outputFontStyles = false, // Default consistent with current API behavior
        public bool $outputTextShadows = true,
        public string|false $indentGuides = false,
        public ?int $indentGuidesTabWidth = null,
        public array $columnGuides = [],
    ) {}

    public static function setDefaultOptionsBuilder(?Closure $builder): void
    {
        static::$default = null;
        static::$defaultOptionsBuilder = $builder;
    }

    public static function default(): Options
    {
        if (! static::$default) {
            if (static::$defaultOptionsBuilder != null) {
                $callback = static::$defaultOptionsBuilder;
                $default = $callback();

                if (! $default instanceof Options) {
                    throw new \LogicException('Default options builder must return an Options instance.');
                }

                static::$default = $default;
            } else {
                static::$default = new Options;
            }
        }

        return self::$default ?? new Options;
    }

    /**
     * @param  list<int|string>  $ranges
     * @return list<array{0:int, 1:int}>
     */
    protected static function parseConfigRanges(array $ranges): array
    {
        $processedRanges = [];

        foreach ($ranges as $range) {
            if (is_string($range) && str_contains($range, '-')) {
                $parts = explode('-', $range, 2);
                $start = (int) $parts[0];
                $end = (int) $parts[1];

                $processedRanges[] = [$start, $end];

                continue;
            }

            $processedRanges[] = [(int) $range, (int) $range];
        }

        return $processedRanges;
    }

    /**
     * @param  string|array<int|string, string>  $optionThemes
     * @return array<int|string, string>
     */
    public static function adjustOptionThemes(string|array $optionThemes): array
    {
        $themes = [];

        if (! is_array($optionThemes)) {
            $optionThemes = [$optionThemes];
        }

        foreach ($optionThemes as $theme) {
            if (str_contains((string) $theme, ':')) {
                $parts = explode(':', (string) $theme, 2);

                $themes[$parts[0]] = $parts[1];

                continue;
            }

            $themes[] = $theme;
        }

        return $themes;
    }

    /** @return array<string, bool|int|string|false|list<int>|null> */
    public function toArray(): array
    {
        return [
            'lineNumbers' => $this->lineNumbersEnabled,
            'lineNumbersStart' => $this->lineNumbersStart,
            'lineNumbersStyle' => $this->lineNumbersStyle,

            'lineNumberAndDiffIndicatorRightPadding' => $this->lineNumberAndDiffIndicatorRightPadding,
            'diffIndicators' => $this->diffIndicatorsEnabled,
            'diffIndicatorsInPlaceOfLineNumbers' => $this->diffIndicatorsInPlaceOfNumbers,
            'diffPreserveSyntaxColors' => $this->diffPreserveSyntaxColors,

            'showSummaryCarets' => $this->showSummaryCarets,
            'summaryCollapsedIndicator' => $this->summaryCollapsedIndicator,

            'fileStyle' => $this->fileStyle,
            'copyable' => $this->copyable,
            'withGutter' => $this->withGutter,

            'classes' => $this->classes,

            'indentGuides' => $this->indentGuides,
            'indentGuidesTabWidth' => $this->indentGuidesTabWidth,
            'columnGuides' => $this->columnGuides,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function fromArray(array $options): Options
    {
        $themeOption = self::themeOption($options, 'theme');

        return new Options(
            lineNumbersEnabled: self::boolOption($options, 'lineNumbers', self::LINE_NUMBERS_ENABLED),
            lineNumbersStart: self::intOption($options, 'lineNumbersStart', self::LINE_NUMBERS_START),
            lineNumbersStyle: self::stringOption($options, 'lineNumbersStyle', self::LINE_NUMBERS_STYLE),

            lineNumberAndDiffIndicatorRightPadding: self::intOption($options, 'lineNumberAndDiffIndicatorRightPadding', self::LINE_NUMBER_AND_DIFF_INDICATOR_RIGHT_PADDING),
            diffIndicatorsEnabled: self::boolOption($options, 'diffIndicators', self::DIFF_INDICATORS_ENABLED),
            diffIndicatorsInPlaceOfNumbers: self::boolOption($options, 'diffIndicatorsInPlaceOfLineNumbers', self::DIFF_INDICATORS_IN_PLACE_OF_NUMBERS),
            diffPreserveSyntaxColors: self::boolOption($options, 'diffPreserveSyntaxColors', self::DIFF_PRESERVE_SYNTAX_COLORS),

            showSummaryCarets: self::boolOption($options, 'showSummaryCarets', self::SHOW_SUMMARY_CARETS),
            summaryCollapsedIndicator: self::stringOption($options, 'summaryCollapsedIndicator', self::SUMMARY_COLLAPSED_INDICATOR),

            annotationsEnabled: self::boolOption($options, 'torchlightAnnotations', self::ANNOTATIONS_ENABLED),
            fileStyle: self::stringOption($options, 'fileStyle', self::FILE_STYLE),
            copyable: self::boolOption($options, 'copyable', self::COPYABLE),
            ariaEnabled: self::boolOption($options, 'ariaEnabled', self::ARIA_ENABLED),
            withGutter: self::boolOption($options, 'withGutter', self::WITH_GUTTER),

            highlightLines: self::parseConfigRanges(self::configRangeOption($options, 'highlightLines')),
            addLines: self::parseConfigRanges(self::configRangeOption($options, 'addLines')),
            removeLines: self::parseConfigRanges(self::configRangeOption($options, 'removeLines')),
            focusLines: self::parseConfigRanges(self::configRangeOption($options, 'focusLines')),
            autolinkLines: self::parseConfigRanges(self::configRangeOption($options, 'autolinkLines')),
            monoLines: self::parseConfigRanges(self::configRangeOption($options, 'monoLines')),
            hideLines: self::parseConfigRanges(self::configRangeOption($options, 'hideLines')),
            themes: self::adjustOptionThemes($themeOption ?? []),

            classes: self::stringOption($options, 'classes', ''),

            indentGuides: self::indentGuidesOption($options, 'indentGuides', false),
            indentGuidesTabWidth: self::nullableIntOption($options, 'indentGuidesTabWidth'),
            columnGuides: self::intListOption($options, 'columnGuides'),
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function mergeWith(array $overrides): Options
    {
        $themeOverride = self::themeOption($overrides, 'theme');

        return new Options(
            lineNumbersEnabled: self::boolOption($overrides, 'lineNumbers', $this->lineNumbersEnabled),
            lineNumbersStart: self::intOption($overrides, 'lineNumbersStart', $this->lineNumbersStart),
            lineNumbersStyle: self::stringOption($overrides, 'lineNumbersStyle', $this->lineNumbersStyle),

            lineNumberAndDiffIndicatorRightPadding: self::intOption($overrides, 'lineNumberAndDiffIndicatorRightPadding', $this->lineNumberAndDiffIndicatorRightPadding),
            diffIndicatorsEnabled: self::boolOption($overrides, 'diffIndicators', $this->diffIndicatorsEnabled),
            diffIndicatorsInPlaceOfNumbers: self::boolOption($overrides, 'diffIndicatorsInPlaceOfLineNumbers', $this->diffIndicatorsInPlaceOfNumbers),
            diffPreserveSyntaxColors: self::boolOption($overrides, 'diffPreserveSyntaxColors', $this->diffPreserveSyntaxColors),

            showSummaryCarets: self::boolOption($overrides, 'showSummaryCarets', $this->showSummaryCarets),
            summaryCollapsedIndicator: self::stringOption($overrides, 'summaryCollapsedIndicator', $this->summaryCollapsedIndicator),

            annotationsEnabled: self::boolOption($overrides, 'torchlightAnnotations', $this->annotationsEnabled),
            fileStyle: self::stringOption($overrides, 'fileStyle', $this->fileStyle),
            copyable: self::boolOption($overrides, 'copyable', $this->copyable),
            ariaEnabled: self::boolOption($overrides, 'ariaEnabled', $this->ariaEnabled),
            withGutter: self::boolOption($overrides, 'withGutter', $this->withGutter),

            highlightLines: array_key_exists('highlightLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'highlightLines'))
                : $this->highlightLines,
            addLines: array_key_exists('addLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'addLines'))
                : $this->addLines,
            removeLines: array_key_exists('removeLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'removeLines'))
                : $this->removeLines,
            focusLines: array_key_exists('focusLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'focusLines'))
                : $this->focusLines,
            autolinkLines: array_key_exists('autolinkLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'autolinkLines'))
                : $this->autolinkLines,
            monoLines: array_key_exists('monoLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'monoLines'))
                : $this->monoLines,
            hideLines: array_key_exists('hideLines', $overrides)
                ? self::parseConfigRanges(self::configRangeOption($overrides, 'hideLines'))
                : $this->hideLines,
            themes: array_key_exists('theme', $overrides)
                ? self::adjustOptionThemes($themeOverride ?? [])
                : $this->themes,

            classes: self::stringOption($overrides, 'classes', $this->classes),
            fallbackOnUnknownGrammar: $this->fallbackOnUnknownGrammar,
            outputFontStyles: $this->outputFontStyles,
            outputTextShadows: $this->outputTextShadows,

            indentGuides: self::indentGuidesOption($overrides, 'indentGuides', $this->indentGuides),
            indentGuidesTabWidth: self::nullableIntOption($overrides, 'indentGuidesTabWidth') ?? $this->indentGuidesTabWidth,
            columnGuides: array_key_exists('columnGuides', $overrides)
                ? self::intListOption($overrides, 'columnGuides')
                : $this->columnGuides,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function boolOption(array $options, string $key, bool $default): bool
    {
        $value = $options[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function intOption(array $options, string $key, int $default): int
    {
        $value = $options[$key] ?? $default;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function nullableIntOption(array $options, string $key): ?int
    {
        $value = $options[$key] ?? null;

        return is_int($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function stringOption(array $options, string $key, string $default): string
    {
        $value = $options[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<int|string>
     */
    private static function configRangeOption(array $options, string $key): array
    {
        $value = $options[$key] ?? [];

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $range): bool => is_int($range) || is_string($range),
        ));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function indentGuidesOption(array $options, string $key, string|false $default): string|false
    {
        $value = $options[$key] ?? $default;

        return is_string($value) || $value === false ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<int>
     */
    private static function intListOption(array $options, string $key): array
    {
        $value = $options[$key] ?? [];

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_int(...)));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return string|array<int|string, string>|null
     */
    private static function themeOption(array $options, string $key): string|array|null
    {
        $value = $options[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        return array_filter(
            $value,
            is_string(...),
        );
    }
}
