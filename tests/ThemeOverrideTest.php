<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Theme\Theme;

test('Theme::override applies custom highlight color', function (): void {
    $engine = new Engine;

    $html = $engine->codeToHtml(
        'x = 1 // [tl! highlight]',
        'php',
        Theme::override('nord', ['editor.lineHighlightBackground' => '#ff000033'])
    );

    expect($html)->toContain('#ff000033');
});

test('plain theme string still works', function (): void {
    $engine = new Engine;

    $html = $engine->codeToHtml('echo "hello";', 'php', 'nord');

    expect($html)->not->toBeEmpty();
});

test('multi-theme with per-theme overrides', function (): void {
    $engine = new Engine;

    $html = $engine->codeToHtml('x = 1 // [tl! highlight]', 'php', [
        'light' => Theme::override('github-light', ['editor.lineHighlightBackground' => '#aabbcc']),
        'dark' => Theme::override('nord', ['editor.lineHighlightBackground' => '#112233']),
    ]);

    expect($html)->toContain('#aabbcc', '#112233');
});

test('Theme::override without overrides returns original theme colors', function (): void {
    $engine = new Engine;

    $htmlWithOverride = $engine->codeToHtml('echo "hello";', 'php', Theme::override('nord', []));
    $htmlNormal = $engine->codeToHtml('echo "hello";', 'php', 'nord');

    expect($htmlWithOverride)->toBe($htmlNormal);
});

test('Theme::override controls diff add background', function (): void {
    $engine = new Engine;

    $block = $engine->renderCode(
        'x = 1 // [tl! add]',
        'php',
        Theme::override('nord', ['torchlight.markupInsertedBackground' => '#00ff0033'])
    );

    expect($block->code)->toContain('#00ff0033');
});

test('Theme::override controls diff remove background', function (): void {
    $engine = new Engine;

    $block = $engine->renderCode(
        'x = 1 // [tl! remove]',
        'php',
        Theme::override('nord', ['torchlight.markupDeletedBackground' => '#ff330033'])
    );

    expect($block->code)->toContain('#ff330033');
});

test('Theme::override controls multiple Torchlight properties', function (): void {
    $engine = new Engine;

    $block = $engine->renderCode(
        "x = 1 // [tl! highlight]\ny = 2 // [tl! add]",
        'php',
        Theme::override('nord', [
            'editor.lineHighlightBackground' => '#111111',
            'torchlight.markupInsertedBackground' => '#222222',
        ])
    );

    expect($block->code)->toContain(
        '#111111',
        '#222222'
    );
});

test('Theme::fromArray creates theme from array', function (): void {
    $engine = new Engine;

    $html = $engine->codeToHtml('echo "hello";', 'php', Theme::fromArray([
        'name' => 'test-theme',
        'colors' => [
            'editor.background' => '#123456',
            'editor.foreground' => '#ffffff',
        ],
        'tokenColors' => [],
    ]));

    expect($html)->toContain('#123456');
});

test('Theme::fromArray with overrides via withOverrides()', function (): void {
    $engine = new Engine;

    $theme = Theme::fromArray([
        'name' => 'test-theme',
        'colors' => [
            'editor.background' => '#000000',
            'editor.foreground' => '#ffffff',
        ],
        'tokenColors' => [],
    ])->withOverrides(['editor.lineHighlightBackground' => '#aabbcc']);

    $html = $engine->codeToHtml('x = 1 // [tl! highlight]', 'php', $theme);

    expect($html)->toContain('#aabbcc');
});

test('Engine::registerTheme registers theme from array', function (): void {
    $engine = new Engine;

    $engine->registerTheme('my-custom', [
        'name' => 'My Custom Theme',
        'colors' => [
            'editor.background' => '#abcdef',
            'editor.foreground' => '#ffffff',
        ],
        'tokenColors' => [],
    ]);

    expect($engine->hasTheme('my-custom'))->toBeTrue();

    $html = $engine->codeToHtml('echo "hi";', 'php', 'my-custom');
    expect($html)->toContain('#abcdef');
});

test('Engine::registerTheme allows overriding registered theme', function (): void {
    $engine = new Engine;

    $engine->registerTheme('my-theme', [
        'name' => 'My Theme',
        'colors' => [
            'editor.background' => '#111111',
            'editor.foreground' => '#ffffff',
        ],
        'tokenColors' => [],
    ]);

    $html = $engine->codeToHtml('x = 1 // [tl! highlight]', 'php',
        Theme::override('my-theme', ['editor.lineHighlightBackground' => '#ff0000'])
    );

    expect($html)->toContain('#ff0000');
});

test('Engine::hasTheme returns false for unregistered themes', function (): void {
    $engine = new Engine;

    expect($engine->hasTheme('nonexistent-theme'))->toBeFalse()
        ->and($engine->hasTheme('nord'))->toBeTrue();
});
