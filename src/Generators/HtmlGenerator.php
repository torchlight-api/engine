<?php

namespace Torchlight\Engine\Generators;

use Phiki\Generators\HtmlGenerator as BaseHtmlGenerator;
use Phiki\Support\Arr;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\TokenSettings;
use Phiki\Token\HighlightedToken;
use Phiki\Token\Token;
use Torchlight\Engine\Generators\Concerns\InteractsWithHtmlRenderer;
use Torchlight\Engine\Generators\Concerns\ManagesStyles;
use Torchlight\Engine\Generators\Concerns\ManagesThemeHooks;
use Torchlight\Engine\Generators\Concerns\ProcessesFileLanguage;
use Torchlight\Engine\Options;
use Torchlight\Engine\Theme\FallbackColors;
use WeakMap;

class HtmlGenerator extends BaseHtmlGenerator
{
    use InteractsWithHtmlRenderer,
        ManagesStyles,
        ManagesThemeHooks,
        ProcessesFileLanguage;

    protected string $vanityLabel = '';

    private WeakMap $tokenOptions;

    private WeakMap $rawTokenContent;

    protected ?GenerationOptions $generationOptions = null;

    protected string $cleanedText = '';

    protected ?Options $torchlightOptions = null;

    protected CharacterRangeDecorator $characterRangeDecorator;

    public function __construct(
        ?string $grammarName,
        array $themes,
        bool $withGutter = false)
    {
        parent::__construct($grammarName, $themes, $withGutter);

        $this->characterRangeDecorator = new CharacterRangeDecorator;

        $this->setHtmlGenerator($this);
        $this->tokenOptions = new WeakMap;
        $this->rawTokenContent = new WeakMap;

        $this->loadDefaultThemeHooks();
    }

    public function setLanguageVanityLabel(string $vanityLabel): static
    {
        $this->vanityLabel = $vanityLabel;

        return $this;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->torchlightOptions = $options;

        return $this;
    }

    protected function setRawContent(Token $token, string $content): static
    {
        $token->text = $content;
        $this->rawTokenContent[$token] = true;

        return $this;
    }

    public function setCleanedText(string $text): static
    {
        $this->cleanedText = $text;

        return $this;
    }

    public function setGenerationOptions(GenerationOptions $generationOptions): static
    {
        $this->generationOptions = $generationOptions;

        return $this;
    }

    public function renderBlock(array $tokens): RenderedBlock
    {
        if ($this->grammarName === 'files') {
            $tokens = $this->processFileLanguage($tokens);
        }

        $block = new RenderedBlock;

        $output = [];
        $attrs = $this->makeThemeAttributes();

        $label = null;

        if ($this->vanityLabel) {
            $label = $this->vanityLabel;
        } elseif ($this->grammarName) {
            $label = $this->grammarName;
        }

        if ($label) {
            $attrs['data-lang'] = htmlspecialchars($label);
        }

        foreach ($tokens as $i => $line) {
            $output[] = $this->buildLine($line, $i);
        }

        $beforeClosing = [];

        if ($this->torchlightOptions->copyable) {
            $beforeClosing[] = $this->makeCopyTarget();
        }

        $classes = array_filter(array_merge([
            'torchlight',
            trim($this->torchlightOptions->classes),
        ], array_keys($this->generationOptions?->blockClasses ?? [])));

        $styles = [];

        foreach ($this->themes as $id => $theme) {
            $themeSelectionBackground = $this->getValueFromTheme($theme, 'torchlight.selectionBackgroundColor');

            if (! $themeSelectionBackground) {
                continue;
            }

            $property = '--theme-selection-background';
            if ($id != $this->getDefaultThemeId()) {
                $property = $this->getPhikiPropertyName($id, 'theme-selection-background');
            }

            $styles[$property] = $themeSelectionBackground;
        }

        $block->attributes = $attrs;
        $block->styles = $styles;
        $block->classes = $classes;

        $block->attributeString = $this->toAttributeString($attrs);
        $block->classString = implode(' ', $block->classes);
        $block->styleString = $this->toStyleString($block->styles);

        // Wrapper styles/classes/etc.

        $wrapperClasses = array_filter([
            'phiki',
            $this->grammarName ? "language-$this->grammarName" : null,
            $this->getDefaultTheme()->name,
            count($this->themes) > 1 ? 'phiki-themes' : null,
        ]);

        foreach ($this->themes as $theme) {
            if ($theme !== $this->getDefaultTheme()) {
                $wrapperClasses[] = $theme->name;
            }
        }

        $block->wrapperClasses = $wrapperClasses;
        $block->wrapperClassString = implode(' ', $wrapperClasses);

        $wrapperStyles = [$this->getDefaultTheme()->base()->toStyleString()];

        foreach ($this->themes as $id => $theme) {
            if ($id !== $this->getDefaultThemeId()) {
                $wrapperStyles[] = $theme->base()->toCssVarString($id);
            }
        }

        $block->wrapperStyles = $wrapperStyles;

        if (count($wrapperStyles) > 0) {
            $block->wrapperStyleString = implode(';', $wrapperStyles);
        }

        $result = implode('', $output);
        $result = strtr($result, $this->generationOptions?->textReplacements ?? []);

        foreach ($this->themes as $id => $theme) {
            $propertyPrefix = '';

            if ($id !== $this->getDefaultThemeId()) {
                $propertyPrefix = $this->getPhikiPropertyName($id, '');
            }

            // Currently the render hooks apply the text shadow/glow.
            // If those have been disabled, we can skip the hooks
            if ($this->torchlightOptions->outputTextShadows) {
                $result = $this->runAfterRenderHooks(
                    $theme->name,
                    $result,
                    $this->torchlightOptions,
                    $propertyPrefix,
                    $id,
                );
            }
        }

        $block->code = implode('', [
            '<!-- Syntax highlighted by Phiki and torchlight.dev -->',
            $result,
            ...$beforeClosing,
        ]);

        return $block;
    }

    public function generate(array $tokens): string
    {
        return $this->buildPre(
            $this->renderBlock($tokens)
        );
    }

    private function buildPre(RenderedBlock $block): string
    {
        return implode('', [
            '<pre>',
            $this->buildCode($block),
            '</pre>',
        ]);
    }

    protected function makeThemeAttributes(): array
    {
        $attributes = [];

        foreach ($this->themes as $id => $theme) {
            $name = htmlspecialchars($theme->name);

            if ($id === $this->getDefaultThemeId()) {
                $attributes['data-theme'] = $name;

                continue;
            }

            $id = htmlspecialchars($id);

            $attributes["data-theme:{$id}"] = $name;
        }

        return $attributes;
    }

    private function buildCode(RenderedBlock $block): string
    {
        return implode('', [
            "<code {$block->attributeString} class='{$block->allClassesToString()}' style='{$block->allStylesToString()}'>",
            $block->code,
            '</code>',
        ]);
    }

    private function makeCopyTarget(): string
    {
        $content = htmlspecialchars($this->cleanedText);

        return "<div aria-hidden='true' hidden tabindex='-1' style='display: none;' class='torchlight-copy-target'>{$content}</div>";
    }

    private function buildLinePrepend(int $line): string
    {
        return implode('', $this->generationOptions?->linePrepends[$line] ?? []);
    }

    private function buildLineAppend(int $line): string
    {
        return implode('', $this->generationOptions?->lineAppends[$line] ?? []);
    }

    private function buildLine(array $line, int $index): string
    {
        $currentLine = $index + 1;
        $output = [];

        if ($this->generationOptions && array_key_exists($currentLine, $this->generationOptions->lineTokenCallbacks)) {
            foreach ($this->generationOptions->lineTokenCallbacks[$currentLine] as $tokenCallback) {
                $line = call_user_func_array($tokenCallback, [$line]);
            }
        }

        $classes = [
            'line',
        ];

        if ($this->generationOptions) {
            if (array_key_exists($currentLine, $this->generationOptions->lineClasses)) {
                $classes = array_merge($classes, array_unique($this->generationOptions->lineClasses[$currentLine]));
            }
        }

        if ($this->withGutter && $this->generationOptions) {
            foreach ($this->generationOptions->gutters as $gutter) {
                if (! $gutter->shouldRender()) {
                    continue;
                }

                $output[] = $gutter->renderLine($index + 1, $index, $line);
            }
        }

        // Store the inner content separately to make it easier to apply character ranges if we have any.
        $innerLineOutput = [];

        foreach ($line as $token) {
            $innerLineOutput[] = $this->buildToken($token);
        }

        $innerContent = implode('', $innerLineOutput);

        if (array_key_exists($index, $this->generationOptions->characterDecorators)) {
            $innerContent = $this->characterRangeDecorator->decorateCharacterRanges(
                $innerContent,
                $this->generationOptions->characterDecorators[$index]
            );
        }

        $output[] = $innerContent;

        $styles = $this->toStyleString($this->getLineStyles($classes));

        if ($styles != '') {
            $styles = "style=\"{$styles}\"";
        }

        $attributes = $this->generationOptions?->lineAttributes[$currentLine] ?? [];
        $attributeString = '';

        if (! empty($attributes)) {
            $attributeString = ' '.$this->toAttributeString($attributes).' ';
        }

        $lineInnerContent = implode($output);

        if ($this->generationOptions && array_key_exists($currentLine, $this->generationOptions->lineContentCallbacks)) {
            foreach ($this->generationOptions->lineContentCallbacks[$currentLine] as $callback) {
                $lineInnerContent = call_user_func_array($callback, [$lineInnerContent, $line]);
            }
        }

        $lineContent = '<div '.$attributeString.$styles.'class=\''.implode(' ', $classes).'\'>'.$lineInnerContent.'</div>';

        return implode('', [
            $this->buildLinePrepend($currentLine),
            $lineContent,
            $this->buildLineAppend($currentLine),
        ]);
    }

    public function getThemeValueStyles(string $propertyName, array|string $themeProp, ?string $default = null): array
    {
        $styles = [];

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

            $themeValue ??= $default;

            if ($id != $this->getDefaultThemeId()) {
                $propName = $this->getPhikiPropertyName($id, $propertyName);
            }

            $styles[$propName] = $themeValue;
        }

        return $styles;
    }

    public function getThemeValueStylesString(string $propertyName, array|string $themeProp, ?string $default = null): string
    {
        return $this->toStyleString($this->getThemeValueStyles($propertyName, $themeProp, $default));
    }

    private function adjustTokenStyles(HighlightedToken $token): HighlightedToken
    {
        $newSettings = [];
        $currentSettings = $token->settings;

        foreach ($this->themes as $id => $theme) {
            if (array_key_exists($id, $currentSettings)) {
                continue;
            }

            $currentSettings[$id] = new TokenSettings(null, null, null);
        }

        foreach ($currentSettings as $id => $settings) {
            $foreground = $settings->foreground;
            $themeName = $this->themes[$id]?->name ?? '';

            if (mb_strlen(trim($token->token->text)) > 0) {
                if ($foreground === null) {
                    $foreground = FallbackColors::getDefaultForeground($themeName);
                }
            }

            $newSettings[$id] = new TokenSettings(
                null,
                $foreground,
                $this->torchlightOptions->outputFontStyles ? $settings->fontStyle : null
            );
        }

        return new HighlightedToken(
            $token->token,
            $newSettings
        );
    }

    public function getTokenStyles(object $token): array
    {
        return $this->getSettingsStylesArray(
            $this->adjustTokenStyles($token)->settings
        );
    }

    public function getSettingsStylesArray(array $tokenSettings): array
    {
        $defaultThemeId = $this->getDefaultThemeId();

        $tokenStyles = [($tokenSettings[$defaultThemeId] ?? null)?->toStyleString()];

        foreach ($tokenSettings as $id => $settings) {
            if ($id !== $defaultThemeId) {
                $tokenStyles[] = $settings->toCssVarString($id);
            }
        }

        return $tokenStyles;
    }

    public function getSettingsStyleString(array $tokenSettings): string
    {
        return $this->toStyleString($this->getSettingsStylesArray($tokenSettings));
    }

    public function buildToken(object $token, array $classes = [], array $styles = []): string
    {
        $tokenStyles = $this->getTokenStyles($token);
        $tokenStyles = array_filter($tokenStyles);
        $styleString = '';

        if (count($tokenStyles) > 0) {
            $styleString = implode(';', $tokenStyles);
        }

        if (! empty($styles)) {
            $incomingStyles = $this->toStyleString($styles);

            if (! str_ends_with($incomingStyles, ';')) {
                $incomingStyles .= ';';
            }

            $styleString = $incomingStyles.$styleString;
        }

        if (empty($classes)) {
            $classes = ['token'];
        }

        $attributes = [];

        if (isset($this->tokenOptions[$token])) {
            $options = $this->tokenOptions[$token];

            if (isset($options['classes'])) {
                $classes = array_merge($classes, $options['classes']);
            }

            if (isset($options['attributes'])) {
                $attributes = array_merge($attributes, $options['attributes']);
            }
        }

        $attributeString = $this->toAttributeString($attributes);

        if ($attributeString != '') {
            $attributeString = ' '.$attributeString;
        }

        if (mb_strlen(trim($styleString)) > 0 && ! str_ends_with($styleString, ';')) {
            $styleString .= ';';
        }

        $styleString = str_replace(';;', ';', $styleString);

        return sprintf(
            '<span class="'.implode(' ', $classes).'"%s%s>%s</span>',
            $styleString ? " style=\"$styleString\"" : null,
            $attributeString,
            $this->getTokenContent($token->token)
        );
    }

    private function getTokenContent(Token $token): string
    {
        if (! isset($this->rawTokenContent[$token])) {
            return htmlspecialchars($token->text);
        }

        return $token->text;
    }

    public function getDefaultTheme(): ParsedTheme
    {
        return Arr::first($this->themes);
    }

    public function getDefaultThemeId(): string
    {
        return Arr::firstKey($this->themes);
    }
}
