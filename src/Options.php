<?php

namespace Torchlight\Engine;

use Closure;
use Phiki\Support\Arr;

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
        public array $highlightLines = [],
        public array $addLines = [],
        public array $removeLines = [],
        public array $focusLines = [],
        public array $autolinkLines = [],
        public array $monoLines = [],
        public array $themes = [],
        public string $classes = '',
        public bool $fallbackOnUnknownGrammar = true,
        public bool $outputFontStyles = false, // Default consistent with current API behavior
        public bool $outputTextShadows = true,
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
                static::$default = $callback();
            } else {
                static::$default = new Options;
            }
        }

        return self::$default;
    }

    protected static function parseConfigRanges(array $ranges): array
    {
        $processedRanges = [];

        foreach ($ranges as $range) {
            if (is_string($range) && str_contains($range, '-')) {
                $parts = explode('-', $range, 2);
                $start = intval($parts[0]);
                $end = intval($parts[1]);

                $processedRanges[] = [$start, $end];

                continue;
            }

            $processedRanges[] = [$range, $range];
        }

        return $processedRanges;
    }

    public static function adjustOptionThemes(string|array $optionThemes): array
    {
        $themes = [];

        if (! is_array($optionThemes)) {
            $optionThemes = [$optionThemes];
        }

        foreach ($optionThemes as $theme) {
            if (str_contains($theme, ':')) {
                $parts = explode(':', $theme, 2);

                $themes[$parts[0]] = $parts[1];

                continue;
            }

            $themes[] = $theme;
        }

        return $themes;
    }

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

            'classes' => $this->classes,
        ];
    }

    public static function fromArray(array $options): Options
    {
        return new Options(
            lineNumbersEnabled: $options['lineNumbers'] ?? self::LINE_NUMBERS_ENABLED,
            lineNumbersStart: $options['lineNumbersStart'] ?? self::LINE_NUMBERS_START,
            lineNumbersStyle: $options['lineNumbersStyle'] ?? self::LINE_NUMBERS_STYLE,

            lineNumberAndDiffIndicatorRightPadding: $options['lineNumberAndDiffIndicatorRightPadding'] ?? self::LINE_NUMBER_AND_DIFF_INDICATOR_RIGHT_PADDING,
            diffIndicatorsEnabled: $options['diffIndicators'] ?? self::DIFF_INDICATORS_ENABLED,
            diffIndicatorsInPlaceOfNumbers: $options['diffIndicatorsInPlaceOfLineNumbers'] ?? self::DIFF_INDICATORS_IN_PLACE_OF_NUMBERS,
            diffPreserveSyntaxColors: $options['diffPreserveSyntaxColors'] ?? self::DIFF_PRESERVE_SYNTAX_COLORS,

            showSummaryCarets: $options['showSummaryCarets'] ?? self::SHOW_SUMMARY_CARETS,
            summaryCollapsedIndicator: $options['summaryCollapsedIndicator'] ?? self::SUMMARY_COLLAPSED_INDICATOR,

            annotationsEnabled: $options['torchlightAnnotations'] ?? self::ANNOTATIONS_ENABLED,
            fileStyle: $options['fileStyle'] ?? self::FILE_STYLE,
            copyable: $options['copyable'] ?? self::COPYABLE,

            highlightLines: self::parseConfigRanges($options['highlightLines'] ?? []),
            addLines: self::parseConfigRanges($options['addLines'] ?? []),
            removeLines: self::parseConfigRanges($options['removeLines'] ?? []),
            focusLines: self::parseConfigRanges($options['focusLines'] ?? []),
            autolinkLines: self::parseConfigRanges($options['autolinkLines'] ?? []),
            monoLines: self::parseConfigRanges($options['monoLines'] ?? []),
            themes: self::adjustOptionThemes(Arr::wrap($options['theme'] ?? [])),

            classes: $options['classes'] ?? '',
        );
    }
}
