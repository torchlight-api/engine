<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Generators\TokenTransformers\IndentGuideTransformer;
use Torchlight\Engine\Options;

test('indent guides are disabled by default', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false));

    $code = "function foo() {\n    return 0;\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain(
        'tl-guide',
        'has-indent-guides'
    );
});

test('html mode adds guide spans with depth classes', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, indentGuides: 'html'));

    $code = "function foo() {\n    return 0;\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'tl-guide',
        'tl-guide-d1',
        'has-indent-guides',
        'indent-guides-html'
    );

    preg_match('/class="tl-guide[^"]*"([^>]*)>/', $html, $m);
    expect($m[1] ?? '')->not->toContain('style=');
});

test('html mode with multiple levels', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, indentGuides: 'html'));

    $code = "if (true) {\n    if (true) {\n        echo 1;\n    }\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'tl-guide-d1',
        'tl-guide-d2'
    );
});

test('ascii mode uses box-drawing characters', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, indentGuides: 'ascii'));

    $code = "function foo() {\n    return 0;\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'tl-guide',
        'tl-guide-d1',
        '│',
        'has-indent-guides',
        'indent-guides-ascii',
    );

    preg_match('/class="tl-guide[^"]*"([^>]*)>/', $html, $m);
    expect($m[1] ?? '')->not->toContain('style=');
});

test('ascii mode with multiple levels', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, indentGuides: 'ascii'));

    $code = "if (true) {\n    if (true) {\n        echo 1;\n    }\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('tl-guide-d1', 'tl-guide-d2');
});

test('indent guides propagate through blank lines', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, indentGuides: 'html'));

    $code = "if (true) {\n    echo 1;\n\n    echo 2;\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect(substr_count($html, 'tl-guide-d1'))->toBeGreaterThanOrEqual(3);
});

test('indent guides respect custom tab width', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(
        withGutter: false,
        indentGuides: 'html',
        indentGuidesTabWidth: 2,
    ));

    $code = "if (true) {\n  echo 1;\n  if (true) {\n    echo 2;\n  }\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('tl-guide-d1', 'tl-guide-d2');
});

test('indent guides do not apply to files grammar', function (): void {
    $transformer = new IndentGuideTransformer;

    expect($transformer->supports('files'))->toBeFalse()
        ->and($transformer->supports('php'))->toBeTrue()
        ->and($transformer->supports('javascript'))->toBeTrue();
});

test('indent guides work with line numbers', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, indentGuides: 'html'));

    $code = "function foo() {\n    return 0;\n}";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('line-number', 'tl-guide');
});

test('non-indented lines get no guides', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, indentGuides: 'html'));

    $code = "echo 1;\necho 2;";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('tl-guide');
});
