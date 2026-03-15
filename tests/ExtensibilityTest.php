<?php

use Torchlight\Engine\Contracts\BlockDecorator;
use Torchlight\Engine\Contracts\TokenTransformer;
use Torchlight\Engine\Engine;
use Torchlight\Engine\Generators\RenderContext;
use Torchlight\Engine\Options;

test('can register custom vanity label', function (): void {
    $engine = new Engine;
    $engine->registerVanityLabel('typescript', 'ts');

    expect($engine->getVanityLabels())->toHaveKey('typescript', 'ts');
});

test('default vanity labels preserved', function (): void {
    $engine = new Engine;

    expect($engine->getVanityLabels())->toHaveKey('php-html', 'php');
});

test('can register custom plain text scope', function (): void {
    $engine = new Engine;
    $engine->registerPlainTextScope('text.markdown.custom');

    expect($engine->getPlainTextScopes())->toContain('text.markdown.custom');
});

test('can unregister plain text scope', function (): void {
    $engine = new Engine;
    $engine->unregisterPlainTextScope('text.bibtex');

    expect($engine->getPlainTextScopes())->not->toContain('text.bibtex');
});

test('default plain text scopes preserved', function (): void {
    $engine = new Engine;

    expect($engine->getPlainTextScopes())->toContain('text.txt', 'text.plain', 'text.bibtex', 'text.csv', 'text.tsv');
});

test('can customize block options keyword', function (): void {
    $engine = new Engine;
    $engine->setBlockOptionsKeyword('code! ');

    expect($engine->getBlockOptionsKeyword())->toBe('code! ');
});

test('default block options keyword preserved', function (): void {
    $engine = new Engine;

    expect($engine->getBlockOptionsKeyword())->toBe('torchlight! ');
});

test('can register custom comment pattern', function (): void {
    $engine = new Engine;
    $engine->registerCommentPattern('source.mylang', '(*', '*)');

    $patterns = $engine->getCommentPatterns();

    expect($patterns['leading'])->toHaveKey('source.mylang', '(*')
        ->and($patterns['trailing'])->toHaveKey('source.mylang', '*)');
});

test('can register leading-only comment pattern', function (): void {
    $engine = new Engine;
    $engine->registerCommentPattern('source.mylang', ';;');

    $patterns = $engine->getCommentPatterns();

    expect($patterns['leading'])->toHaveKey('source.mylang', ';;')
        ->and($patterns['trailing'])->not->toHaveKey('source.mylang');
});

test('can bulk register comment patterns', function (): void {
    $engine = new Engine;
    $engine->registerCommentPatterns([
        'source.lang1' => ['leading' => '(*', 'trailing' => '*)'],
        'source.lang2' => ['leading' => ';;'],
    ]);

    $patterns = $engine->getCommentPatterns();

    expect($patterns['leading'])->toHaveKey('source.lang1', '(*')
        ->and($patterns['trailing'])->toHaveKey('source.lang1', '*)')
        ->and($patterns['leading'])->toHaveKey('source.lang2', ';;');
});

test('default comment patterns preserved', function (): void {
    $engine = new Engine;
    $patterns = $engine->getCommentPatterns();

    expect($patterns['leading'])->toHaveKey('source.coq', '(*')
        ->and($patterns['leading'])->toHaveKey('source.abap', '"')
        ->and($patterns['trailing'])->toHaveKey('source.coq', '*)');
});

test('default gutters are registered', function (): void {
    $engine = new Engine;

    expect($engine->hasGutter('line-numbers'))->toBeTrue()
        ->and($engine->hasGutter('diff'))->toBeTrue()
        ->and($engine->hasGutter('collapse'))->toBeTrue();
});

test('can remove default gutter', function (): void {
    $engine = new Engine;
    $engine->removeGutter('diff');

    expect($engine->hasGutter('diff'))->toBeFalse()
        ->and($engine->hasGutter('line-numbers'))->toBeTrue();
});

test('can get all gutters', function (): void {
    $engine = new Engine;
    $gutters = $engine->getGutters();

    expect($gutters)->toHaveKeys(['line-numbers', 'diff', 'collapse']);
});

test('can register grammar transformer', function (): void {
    $engine = new Engine;
    $engine->registerGrammarTransformer('blade', function ($code, $grammar) {
        if (str_contains($code, '@extends')) {
            return 'blade-php';
        }

        return null;
    });

    expect($engine->getGrammarTransformers())->toHaveKey('blade');
});

test('can disable php transformer', function (): void {
    $engine = new Engine;
    $engine->removeGrammarTransformers('php');

    expect($engine->getGrammarTransformers())->not->toHaveKey('php');
});

test('default php transformer preserved', function (): void {
    $engine = new Engine;

    expect($engine->getGrammarTransformers())->toHaveKey('php');
});

test('can register token transformer factory', function (): void {
    $engine = new Engine;

    $engine->registerTokenTransformerFactory(fn () => new class implements TokenTransformer
    {
        public function transform(RenderContext $context, array $tokens): array
        {
            return $tokens;
        }

        public function supports(string $grammarName): bool
        {
            return $grammarName === 'mermaid';
        }
    });

    expect($engine->getTokenTransformerFactories())->toHaveCount(3);
});

test('can clear token transformer factories', function (): void {
    $engine = new Engine;
    $engine->clearTokenTransformerFactories();

    expect($engine->getTokenTransformerFactories())->toBeEmpty();
});

test('file tree rendering still works with default transformer', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
resources/
  views/
    home.blade.php
CODE;

    $html = $engine->codeToHtml($code, 'files', 'github-light');

    expect($html)->toContain('tl-files-folder', 'tl-files-file');
});

test('can register block decorator factory', function (): void {
    $engine = new Engine;

    $engine->registerBlockDecoratorFactory(fn () => new class implements BlockDecorator
    {
        public function shouldRender(RenderContext $context): bool
        {
            return true;
        }

        public function render(RenderContext $context, string $cleanedText): string
        {
            return '<div class="custom">test</div>';
        }

        public function getPriority(): int
        {
            return 50;
        }
    });

    expect($engine->getBlockDecoratorFactories())->toHaveCount(2);
});

test('can clear block decorator factories', function (): void {
    $engine = new Engine;
    $engine->clearBlockDecoratorFactories();

    expect($engine->getBlockDecoratorFactories())->toBeEmpty();
});

test('renderCode respects the withGutter option', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false));

    $html = $engine->renderCode('echo "hello";', 'php', 'github-light')->toHtml();

    expect($html)->not->toContain('class="line-number"');
});
