<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

function uaHtml(string $code, string $grammar = 'yaml', string $theme = 'github-light'): string
{
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    return $engine->codeToHtml($code, $grammar, $theme);
}

test('highlight works in YAML via universal // syntax', function (): void {
    $code = <<<'CODE'
key: value // [tl! highlight]
second: line
CODE;

    $html = uaHtml($code);

    expect($html)->toContain('line-highlight', 'value');
    expect($html)->not->toContain('// [tl!');
});

test('highlight works in HTML via universal // syntax', function (): void {
    $code = <<<'CODE'
<div class="test"> // [tl! highlight]
  <p>Hello</p>
</div>
CODE;

    $html = uaHtml($code, 'html');

    expect($html)->toContain('line-highlight')
        ->and($html)->not->toContain('// [tl!');
});

test('highlight works in CSS via universal // syntax', function (): void {
    $code = <<<'CODE'
body { // [tl! highlight]
  color: red;
}
CODE;

    $html = uaHtml($code, 'css');

    expect($html)->toContain('line-highlight')
        ->and($html)->not->toContain('// [tl!');
});

test('highlight still works in PHP', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! highlight]
$b = 2;
CODE;

    $html = uaHtml($code, 'php');

    expect($html)->toContain('line-highlight')
        ->and($html)->not->toContain('// [tl!')
        ->and(substr_count($html, 'line-highlight'))->toBe(1);
});

test('highlight still works in JavaScript', function (): void {
    $code = <<<'CODE'
const a = 1; // [tl! highlight]
const b = 2;
CODE;

    $html = uaHtml($code, 'javascript');

    expect($html)->toContain('line-highlight')
        ->and(substr_count($html, 'line-highlight'))->toBe(1);
});

test('annotation and // are stripped from output', function (): void {
    $code = <<<'CODE'
key: value // [tl! highlight]
CODE;

    $html = uaHtml($code);

    expect($html)->not->toContain('//', '[tl!');
    expect($html)->toContain('value');
});

test('works with no space after //', function (): void {
    $code = <<<'CODE'
key: value //[tl! highlight]
CODE;

    $html = uaHtml($code);

    expect($html)->toContain('line-highlight')
        ->and($html)->not->toContain('//');
});

test('works with extra spaces after //', function (): void {
    $code = <<<'CODE'
key: value //   [tl! highlight]
CODE;

    $html = uaHtml($code);

    expect($html)->toContain('line-highlight')
        ->and($html)->not->toContain('//');
});

test('line with only annotation is removed', function (): void {
    $code = <<<'CODE'
first line
// [tl! highlight]
third line
CODE;

    $html = uaHtml($code);

    expect($html)->toContain('line-highlight')
        ->and(substr_count($html, "class='line"))->toBe(2);
});

test('annotations with method args work', function (): void {
    $code = <<<'CODE'
key: value // [tl! lens(Above)]
CODE;

    $html = uaHtml($code);

    expect($html)->toContain('codelens', 'Above');
    expect($html)->not->toContain('//');
});

test('multiple annotations in one block work', function (): void {
    $code = <<<'CODE'
key: value // [tl! highlight .my-class]
CODE;

    $html = uaHtml($code);

    expect($html)->toContain('line-highlight', 'my-class');
});

test('range annotations work with universal syntax', function (): void {
    $code = <<<'CODE'
first: line // [tl! highlight:1]
second: line
third: line
CODE;

    $html = uaHtml($code);

    expect(substr_count($html, 'line-highlight'))->toBe(2);
});
