<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('method args with dots are not treated as CSS class prefix', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$key = env("APP_KEY"); // [tl! link("https://example.com/.env")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('https://example.com/.env')
        ->and($html)->not->toContain('class=\'env');
});

test('method args with hash are not treated as ID prefix', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$el = document.querySelector("#main"); // [tl! link("https://example.com/#main")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('https://example.com/#main');
});

test('method args with multiple spaces', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! link("https://example.com/a long path with spaces")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('https://example.com/a long path with spaces');
});

test('method args with parentheses inside', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$fn = fn($x) => $x; // [tl! link("https://example.com/fn()")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('https://example.com/fn()');
});

test('method args with special HTML characters', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! link("https://example.com/<int>")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-links');
});

test('multiple annotations on same line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! highlight focus]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-highlight-lines',
        'has-focus-lines',
        'line-highlight',
        'line-focus'
    );
});

test('annotation with method args followed by another annotation', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! link("https://example.com") highlight]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-links',
        'has-highlight-lines'
    );
});

test('class and id annotations combined', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! .my-class#my-id]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'my-class',
        "id='my-id'"
    );
});

test('annotation with range after method args with spaces', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1; // [tl! link("https://example.com"):2]
    $b = 2;
    $c = 3;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-links')
        ->and(substr_count($html, 'https://example.com'))->toBeGreaterThanOrEqual(2);
});

test('annotation with character range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! highlight:c1,5]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('char-highlight');
});

test('annotation with all range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1; // [tl! highlight:all]
    $b = 2;
    $c = 3;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-highlight-lines')
        ->and(substr_count($html, 'line-highlight'))->toBe(3);
});

test('unknown annotation name is ignored gracefully', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! nonexistent_annotation]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        '<pre>',
        '</pre>',
    );
});

test('empty annotation block is handled', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! ]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('<pre>');
});

test('annotation in double-slash comment', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! highlight]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('line-highlight');
});

test('annotation in hash comment', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = 'x = 1 # [tl! highlight]';

    $html = $engine->codeToHtml($code, 'python', 'nord');

    expect($html)->toContain('line-highlight');
});

test('css class annotation with line range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1; // [tl! .custom-class:2]
    $b = 2;
    $c = 3;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect(substr_count($html, 'custom-class'))->toBeGreaterThanOrEqual(2);
});

test('id annotation on single line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$key = env("APP_KEY"); // [tl! #config-key]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain("id='config-key'");
});

test('annotation does not appear in rendered code', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! highlight]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('[tl!', 'tl!');
});

test('multiple annotation blocks on same line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! highlight] [tl! focus]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-highlight-lines',
        'has-focus-lines'
    );
});

test('annotation with option after method args', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$items = [1, 2, 3]; // [tl! mark("items") all]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('has-mark-lines');
});

test('diff annotations with focus', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $price = 100; // [tl! -- focus]
    $price = 150; // [tl! ++ focus]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-remove-lines',
        'has-add-lines',
        'has-focus-lines'
    );
});

test('highlight with link', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = important(); // [tl! highlight link("https://example.com")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-highlight-lines',
        'has-links',
        'https://example.com'
    );
});
