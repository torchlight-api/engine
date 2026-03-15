<?php

namespace Torchlight\Engine\Generators;

use Phiki\Highlighting\Highlighter;
use Phiki\Theme\ParsedTheme;
use Phiki\Token\HighlightedToken;
use Torchlight\Engine\Contracts\BlockDecorator;
use Torchlight\Engine\Contracts\TokenTransformer;
use Torchlight\Engine\Generators\Concerns\ManagesThemeHooks;
use Torchlight\Engine\Generators\Concerns\MergesHighlightedTokens;
use Torchlight\Engine\Generators\TokenTransformers\IndentGuideTransformer;
use Torchlight\Engine\Options;

class HtmlGenerator
{
    use ManagesThemeHooks,
        MergesHighlightedTokens;

    /**
     * @var TokenTransformer[]
     */
    protected array $tokenTransformers = [];

    /**
     * @var BlockDecorator[]
     */
    protected array $blockDecorators = [];

    protected string $vanityLabel = '';

    protected ?GenerationOptions $generationOptions = null;

    protected string $cleanedText = '';

    protected ?Options $torchlightOptions = null;

    protected CharacterRangeDecorator $characterRangeDecorator;

    protected ?ThemeStyleResolver $themeResolver = null;

    /**
     * @param  array<string, ParsedTheme>  $themes
     */
    public function __construct(
        protected ?string $grammarName,
        /** @var array<string, ParsedTheme> */
        protected array $themes,
    ) {
        $this->characterRangeDecorator = new CharacterRangeDecorator;

        $this->loadDefaultThemeHooks();
    }

    public function setThemeResolver(ThemeStyleResolver $resolver): static
    {
        $this->themeResolver = $resolver;

        return $this;
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        $this->themeResolver?->setHighlighter($highlighter);

        return $this;
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

    /**
     * Register a token transformer.
     */
    public function registerTokenTransformer(TokenTransformer $transformer): static
    {
        $this->tokenTransformers[] = $transformer;

        return $this;
    }

    /**
     * Get all registered token transformers.
     *
     * @return TokenTransformer[]
     */
    public function getTokenTransformers(): array
    {
        return $this->tokenTransformers;
    }

    /**
     * Register a block decorator.
     * Decorators are sorted by priority (lower = earlier).
     */
    public function registerBlockDecorator(BlockDecorator $decorator): static
    {
        $this->blockDecorators[] = $decorator;

        usort($this->blockDecorators, fn ($a, $b) => $a->getPriority() <=> $b->getPriority());

        return $this;
    }

    /**
     * Get all registered block decorators.
     *
     * @return BlockDecorator[]
     */
    public function getBlockDecorators(): array
    {
        return $this->blockDecorators;
    }

    /**
     * Apply all block decorators that should render.
     *
     * @param  RenderContext  $context  The render context
     * @param  string  $cleanedText  Plain text content for decorators
     * @return string[] Array of HTML strings to append
     */
    protected function applyBlockDecorators(RenderContext $context, string $cleanedText): array
    {
        $output = [];

        foreach ($this->blockDecorators as $decorator) {
            if ($decorator->shouldRender($context)) {
                $output[] = $decorator->render($context, $cleanedText);
            }
        }

        return $output;
    }

    /**
     * Apply all token transformers that support the current grammar.
     */
    /**
     * @param  array<int, array<int, RenderableToken>>  $tokens
     * @return array<int, array<int, RenderableToken>>
     */
    protected function applyTokenTransformers(array $tokens): array
    {
        $context = new RenderContext(
            $this->torchlightOptions(),
            $this->themes,
            $this->grammarName ?? '',
            $this->themeResolver(),
            $this,
        );

        foreach ($this->tokenTransformers as $transformer) {
            if ($transformer->supports($this->grammarName ?? '')) {
                $tokens = $transformer->transform($context, $tokens);
            }
        }

        /** @var array<int, array<int, RenderableToken>> $tokens */
        return $tokens;
    }

    /**
     * Convert HighlightedToken[][] to RenderableToken[][] for processing.
     *
     * @param  array<int, array<int, HighlightedToken>>  $tokens
     * @return array<int, array<int, RenderableToken>>
     */
    protected function wrapTokens(array $tokens): array
    {
        $wrapped = [];

        foreach ($tokens as $lineIndex => $lineTokens) {
            $wrapped[$lineIndex] = [];

            foreach ($lineTokens as $token) {
                $wrapped[$lineIndex][] = RenderableToken::from($token);
            }
        }

        return $wrapped;
    }

    /**
     * @param  array<int, array<int, HighlightedToken>>  $tokens
     */
    public function renderBlock(array $tokens): RenderedBlock
    {
        // Resolve codelens indent guide placeholders BEFORE transformation,
        $this->resolveCodelensIndentGuides($tokens);

        $renderableTokens = $this->prepareTokensForRendering($tokens);

        $this->applyColumnGuideClasses();

        if ($this->torchlightOptions()->indentGuides !== false) {
            $generationOptions = $this->generationOptions();
            $generationOptions->blockClasses['has-indent-guides'] = true;
            $generationOptions->blockClasses['indent-guides-'.$this->torchlightOptions()->indentGuides] = true;
        }

        $output = [];
        $removedLines = $this->generationOptions()->removedLines;

        foreach ($renderableTokens as $i => $line) {
            if (isset($removedLines[$i + 1])) {
                continue;
            }

            $output[] = $this->buildLine($line, $i);
        }

        $context = $this->createRenderContext();

        $block = new RenderedBlock;
        $this->buildBlockMetadata($block);
        $this->buildWrapperMetadata($block);

        $beforeClosing = $this->applyBlockDecorators($context, $this->cleanedText);

        $result = implode('', $output);
        $result = strtr($result, $this->generationOptions()->textReplacements);
        $result = $this->applyPostRenderHooks($result);

        $block->code = implode('', [
            '<!-- Syntax highlighted by Phiki and torchlight.dev -->',
            $result,
            ...$beforeClosing,
        ]);

        return $block;
    }

    /**
     * @param  array<int, array<int, HighlightedToken>>  $tokens
     * @return array<int, array<int, RenderableToken>>
     */
    protected function prepareTokensForRendering(array $tokens): array
    {
        $renderableTokens = $this->wrapTokens($tokens);

        $renderableTokens = $this->applyTokenTransformers($renderableTokens);

        /** @var array<int, array<int, RenderableToken>> $mergedTokens */
        $mergedTokens = $this->mergeHighlightedTokens($renderableTokens);

        return $mergedTokens;
    }

    protected function createRenderContext(): RenderContext
    {
        return new RenderContext(
            $this->torchlightOptions(),
            $this->themes,
            $this->grammarName ?? '',
            $this->themeResolver(),
            $this,
        );
    }

    protected function buildBlockMetadata(RenderedBlock $block): void
    {
        $attrs = $this->makeThemeAttributes();

        $label = $this->vanityLabel ?: $this->grammarName;
        if ($label) {
            $attrs['data-lang'] = htmlspecialchars($label);
        }

        if ($this->torchlightOptions()->ariaEnabled) {
            $attrs['role'] = 'region';
            $attrs['tabindex'] = '0';
            $ariaLabel = $label ? "Code block: {$label}" : 'Code block';
            $attrs['aria-label'] = htmlspecialchars($ariaLabel);
        }

        $classes = array_values(array_filter(array_merge([
            'torchlight',
            trim($this->torchlightOptions()->classes),
        ], array_keys($this->generationOptions()->blockClasses))));

        $styles = $this->buildSelectionBackgroundStyles();

        $columns = $this->torchlightOptions()->columnGuides;
        if ($columns !== []) {
            $styles['--tl-colguide-max'] = (string) max($columns);
        }

        $block->attributes = $attrs;
        $block->styles = $styles;
        $block->classes = $classes;

        $block->attributeString = $this->themeResolver()->toAttributeString($attrs);
        $block->classString = implode(' ', $block->classes);
        $block->styleString = $this->themeResolver()->toStyleString($block->styles);
    }

    /** @return array<string, string> */
    protected function buildSelectionBackgroundStyles(): array
    {
        $styles = [];

        foreach ($this->themes as $id => $theme) {
            $themeSelectionBackground = $this->themeResolver()->getValueFromTheme($theme, 'torchlight.selectionBackgroundColor');

            if (! $themeSelectionBackground) {
                continue;
            }

            $property = '--theme-selection-background';
            if ($id != $this->themeResolver()->getDefaultThemeId()) {
                $property = $this->themeResolver()->getPhikiPropertyName($id, 'theme-selection-background');
            }

            $styles[$property] = $themeSelectionBackground;
        }

        return $styles;
    }

    protected function applyColumnGuideClasses(): void
    {
        $columns = $this->torchlightOptions()->columnGuides;

        if ($columns === []) {
            return;
        }

        $genOptions = $this->generationOptions();
        $genOptions->globalLineClasses = ColumnGuideApplicator::computeLineClasses($columns);
        $genOptions->columnGuideHtml = ColumnGuideApplicator::computeGuideHtml($columns);
        $genOptions->blockClasses['has-column-guides'] = true;
    }

    /**
     * @param  array<int, array<int, HighlightedToken>>  $originalTokens
     */
    private function resolveCodelensIndentGuides(array $originalTokens): void
    {
        if (empty($this->generationOptions()->codelensIndentPlaceholders)) {
            return;
        }

        $mode = $this->torchlightOptions()->indentGuides;

        if ($mode === false) {
            return;
        }

        $tabWidth = $this->torchlightOptions()->indentGuidesTabWidth
            ?? $this->detectTabWidthFromTokens($originalTokens);

        if ($tabWidth < 1) {
            $tabWidth = 4;
        }

        foreach ($this->generationOptions()->codelensIndentPlaceholders as $placeholder => $line) {
            $index = $line - 1;
            $indent = $this->measureLineIndent($originalTokens[$index] ?? [], $tabWidth);

            if ($indent === 0) {
                $this->generationOptions()->textReplacements[$placeholder] = '';

                continue;
            }

            $levels = intdiv($indent, $tabWidth);

            $this->generationOptions()->textReplacements[$placeholder] = IndentGuideTransformer::renderGuideSpans(
                $levels, $tabWidth, $indent, $mode
            );
        }
    }

    /**
     * @param  array<int, array<int, HighlightedToken>>  $tokens
     */
    private function detectTabWidthFromTokens(array $tokens): int
    {
        $indentSizes = [];

        foreach ($tokens as $lineTokens) {
            if (empty($lineTokens)) {
                continue;
            }

            /** @var HighlightedToken $firstToken */
            $firstToken = $lineTokens[0];
            $text = $firstToken->token->text;

            // Only measure whitespace-only tokens for indent detection.
            if ($text === '' || trim((string) $text) !== '') {
                continue;
            }

            $size = strlen(str_replace("\t", '    ', $text));

            if ($size > 0) {
                $indentSizes[] = $size;
            }
        }

        return IndentGuideTransformer::computeTabWidth($indentSizes);
    }

    /**
     * @param  HighlightedToken[]  $lineTokens
     */
    private function measureLineIndent(array $lineTokens, int $tabWidth): int
    {
        $columns = 0;

        foreach ($lineTokens as $token) {
            $text = $token->token->text;

            if (trim($text) !== '') {
                break;
            }

            for ($i = 0; $i < strlen($text); $i++) {
                if ($text[$i] === "\t") {
                    $columns += $tabWidth - ($columns % $tabWidth);
                } else {
                    $columns++;
                }
            }
        }

        return $columns;
    }

    protected function buildWrapperMetadata(RenderedBlock $block): void
    {
        $wrapperClasses = array_values(array_filter([
            'phiki',
            $this->grammarName ? "language-$this->grammarName" : null,
            $this->themeResolver()->getDefaultTheme()->name,
            count($this->themes) > 1 ? 'phiki-themes' : null,
        ]));

        foreach ($this->themes as $theme) {
            if ($theme !== $this->themeResolver()->getDefaultTheme()) {
                $wrapperClasses[] = $theme->name;
            }
        }

        $block->wrapperClasses = $wrapperClasses;
        $block->wrapperClassString = implode(' ', $wrapperClasses);

        $wrapperStyles = [$this->themeResolver()->getDefaultTheme()->base()->toStyleString()];

        foreach ($this->themes as $id => $theme) {
            if ($id !== $this->themeResolver()->getDefaultThemeId()) {
                $wrapperStyles[] = $theme->base()->toCssVarString($id);
            }
        }

        $block->wrapperStyles = $wrapperStyles;

        if (count($wrapperStyles) > 0) {
            $block->wrapperStyleString = implode(';', $wrapperStyles);
        }
    }

    protected function applyPostRenderHooks(string $result): string
    {
        if (! $this->torchlightOptions()->outputTextShadows) {
            return $result;
        }

        foreach ($this->themes as $id => $theme) {
            $propertyPrefix = '';

            if ($id !== $this->themeResolver()->getDefaultThemeId()) {
                $propertyPrefix = $this->themeResolver()->getPhikiPropertyName($id, '');
            }

            $result = $this->runAfterRenderHooks(
                $theme->name,
                $result,
                $this->torchlightOptions(),
                $propertyPrefix,
                $id,
            );
        }

        return $result;
    }

    /** @return array<string, string> */
    protected function makeThemeAttributes(): array
    {
        $attributes = [];

        foreach ($this->themes as $id => $theme) {
            $name = htmlspecialchars((string) $theme->name);

            if ($id === $this->themeResolver()->getDefaultThemeId()) {
                $attributes['data-theme'] = $name;

                continue;
            }

            $id = htmlspecialchars((string) $id);

            $attributes["data-theme:{$id}"] = $name;
        }

        return $attributes;
    }

    private function buildLinePrepend(int $line): string
    {
        return implode('', $this->generationOptions()->linePrepends[$line] ?? []);
    }

    private function buildLineAppend(int $line): string
    {
        return implode('', $this->generationOptions()->lineAppends[$line] ?? []);
    }

    /**
     * @param  array<int, RenderableToken>  $line
     */
    private function buildLine(array $line, int $index): string
    {
        $currentLine = $index + 1;

        $line = $this->applyLineTokenCallbacks($line, $currentLine);

        $this->applyGutterLineDecorations($index);

        $classes = $this->buildLineClasses($currentLine);

        $innerContent = $this->buildInnerLineContent($line, $index);
        $innerContent = $this->applyLineContentCallbacks($innerContent, $line, $currentLine);

        $gutterContent = $this->buildGutterContent($index, $line);
        $guideHtml = $this->generationOptions()->columnGuideHtml;
        $lineInnerContent = $guideHtml.implode('', array_merge($gutterContent, [$innerContent]));

        $lineElement = $this->buildLineElement($classes, $lineInnerContent, $currentLine);

        return implode('', [
            $this->buildLinePrepend($currentLine),
            $lineElement,
            $this->buildLineAppend($currentLine),
        ]);
    }

    /**
     * @param  array<int, RenderableToken>  $line
     * @return array<int, RenderableToken>
     */
    private function applyLineTokenCallbacks(array $line, int $currentLine): array
    {
        $generationOptions = $this->generationOptions;
        if ($generationOptions === null || ! array_key_exists($currentLine, $generationOptions->lineTokenCallbacks)) {
            return $line;
        }

        foreach ($generationOptions->lineTokenCallbacks[$currentLine] as $tokenCallback) {
            $line = $tokenCallback($line);
        }

        return $line;
    }

    /** @return list<string> */
    private function buildLineClasses(int $currentLine): array
    {
        $classes = ['line'];

        if ($this->generationOptions && array_key_exists($currentLine, $this->generationOptions->lineClasses)) {
            $classes = array_merge($classes, array_unique($this->generationOptions->lineClasses[$currentLine]));
        }

        if ($this->generationOptions && $this->generationOptions->globalLineClasses !== []) {
            $classes = array_merge($classes, $this->generationOptions->globalLineClasses);
        }

        return $classes;
    }

    private function applyGutterLineDecorations(int $index): void
    {
        if (! $this->torchlightOptions?->withGutter || ! $this->generationOptions) {
            return;
        }

        foreach ($this->generationOptions->getSortedGutters() as $gutter) {
            if ($gutter->shouldRender()) {
                $gutter->decorateLine($index + 1, $index, $this->generationOptions);
            }
        }
    }

    /**
     * @param  array<int, RenderableToken>  $line
     * @return list<string>
     */
    private function buildGutterContent(int $index, array $line): array
    {
        $output = [];

        if (! $this->torchlightOptions?->withGutter || ! $this->generationOptions) {
            return $output;
        }

        $gutters = $this->generationOptions->getSortedGutters();

        foreach ($gutters as $gutter) {
            if ($gutter->shouldRender()) {
                $output[] = $gutter->renderLine($index + 1, $index, $line);
            }
        }

        return $output;
    }

    /**
     * @param  array<int, RenderableToken>  $line
     */
    private function buildInnerLineContent(array $line, int $index): string
    {
        $innerLineOutput = [];

        foreach ($line as $token) {
            $innerLineOutput[] = $this->buildToken($token);
        }

        $innerContent = implode('', $innerLineOutput);

        $generationOptions = $this->generationOptions();

        if (array_key_exists($index, $generationOptions->characterDecorators)) {
            $innerContent = $this->characterRangeDecorator->decorateCharacterRanges(
                $innerContent,
                $generationOptions->characterDecorators[$index]
            );
        }

        return $innerContent;
    }

    /**
     * @param  array<int, RenderableToken>  $line
     */
    private function applyLineContentCallbacks(string $content, array $line, int $currentLine): string
    {
        $generationOptions = $this->generationOptions;
        if ($generationOptions === null || ! array_key_exists($currentLine, $generationOptions->lineContentCallbacks)) {
            return $content;
        }

        foreach ($generationOptions->lineContentCallbacks[$currentLine] as $callback) {
            $content = $callback($content, $line);
        }

        return $content;
    }

    /**
     * @param  list<string>  $classes
     */
    private function buildLineElement(array $classes, string $content, int $currentLine): string
    {
        $lineStyles = $this->themeResolver()->getLineStyles($classes);
        $styles = $this->themeResolver()->toStyleString($lineStyles);
        $styleAttr = $styles !== '' ? "style=\"{$styles}\"" : '';

        $attributes = $this->generationOptions()->lineAttributes[$currentLine] ?? [];
        $attributeString = ! empty($attributes)
            ? ' '.$this->themeResolver()->toAttributeString($attributes).' '
            : '';

        return '<div '.$attributeString.$styleAttr.'class=\''.implode(' ', $classes).'\'>'.$content.'</div>';
    }

    /**
     * @param  list<string>  $classes
     * @param  array<int|string, string>  $styles
     */
    public function buildToken(RenderableToken $token, array $classes = [], array $styles = []): string
    {
        $highlighted = $token->highlighted;
        $metadata = $token->metadata;

        $tokenStyles = array_filter($this->themeResolver()->getTokenStyles($highlighted));
        $styleString = $this->themeResolver()->toStyleString(array_merge($styles, $tokenStyles));

        if ($styleString !== '' && ! str_ends_with($styleString, ';')) {
            $styleString .= ';';
        }
        $styleString = str_replace(';;', ';', $styleString);

        if (empty($classes)) {
            $classes = ['token'];
        }

        $attributes = [];
        if ($metadata->hasClasses()) {
            $classes = array_merge($classes, $metadata->classes);
        }
        if ($metadata->hasAttributes()) {
            $attributes = array_merge($attributes, $metadata->attributes);
        }

        $attributeString = $this->themeResolver()->toAttributeString($attributes);

        if ($attributeString != '') {
            $attributeString = ' '.$attributeString;
        }

        return sprintf(
            '<span class="'.implode(' ', $classes).'"%s%s>%s</span>',
            $styleString ? " style=\"$styleString\"" : null,
            $attributeString,
            $this->getTokenContent($highlighted, $metadata)
        );
    }

    private function getTokenContent(HighlightedToken $token, ?TokenMetadata $metadata = null): string
    {
        // If metadata indicates raw content, don't escape
        if ($metadata !== null && $metadata->isRaw()) {
            return $token->token->text;
        }

        return htmlspecialchars($token->token->text);
    }

    private function torchlightOptions(): Options
    {
        if ($this->torchlightOptions === null) {
            throw new \LogicException('Torchlight options have not been configured.');
        }

        return $this->torchlightOptions;
    }

    private function generationOptions(): GenerationOptions
    {
        if ($this->generationOptions === null) {
            throw new \LogicException('Generation options have not been configured.');
        }

        return $this->generationOptions;
    }

    private function themeResolver(): ThemeStyleResolver
    {
        if ($this->themeResolver === null) {
            throw new \LogicException('Theme resolver has not been configured.');
        }

        return $this->themeResolver;
    }
}
