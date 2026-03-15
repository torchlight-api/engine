<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

function lensHtml(string $code, string $grammar = 'php', string $theme = 'github-light'): string
{
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    return $engine->codeToHtml($code, $grammar, $theme);
}

function lensHtmlNoGutter(string $code, string $grammar = 'php', string $theme = 'github-light'): string
{
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false));

    return $engine->codeToHtml($code, $grammar, $theme);
}

test('simple text lens renders above line', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(First Line)]
$b = 2;
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("class='codelens'", "<span class='codelens-item'>First Line</span>");
});

test('lens content appears before the line div', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(Above)]
CODE;

    $html = lensHtml($code);

    $codelensPos = strpos($html, "class='codelens'");
    $linePos = strpos($html, "class='line'");

    expect($codelensPos)->toBeLessThan($linePos);
});

test('key-value lens renders structured spans', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(Author: John)]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain(
        "<span class='codelens-key'>Author</span>: <span class='codelens-value'>John</span>",
        "class='codelens-item'"
    );
});

test('key-value with url in value works correctly', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(Link: https://example.com)]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain(
        "<span class='codelens-key'>Link</span>: <span class='codelens-value'>https://example.com</span>"
    );
});

test('comma-separated items render with separators', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(item1, item2)]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain(
        "<span class='codelens-item'>item1</span>",
        "<span class='codelens-separator'> | </span>",
        "<span class='codelens-item'>item2</span>"
    );
});

test('mixed plain and key-value items', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(plain text, key: value)]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain(
        "<span class='codelens-item'>plain text</span>",
        "<span class='codelens-key'>key</span>: <span class='codelens-value'>value</span>"
    );
});

test('single-quoted string with comma is one item', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens('hello, world')]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("<span class='codelens-item'>hello, world</span>")
        ->and($html)->not->toContain('codelens-separator');
});

test('double-quoted string with comma is one item', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens("hello, world")]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("<span class='codelens-item'>hello, world</span>")
        ->and($html)->not->toContain('codelens-separator');
});

test('mixed quoted and unquoted items', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens('first, item', second)]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain(
        "<span class='codelens-item'>first, item</span>",
        "<span class='codelens-item'>second</span>", 'codelens-separator'
    );
});

test('escaped quote inside quoted string', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens('it\'s here')]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain('it&#039;s here');
});

test('quoted key-value pair preserves colon parsing', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(Author: 'Jane, Doe')]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("<span class='codelens-key'>Author</span>: <span class='codelens-value'>Jane, Doe</span>");
});

test('multiple lens annotations on same line stack as separate rows', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(first) lens(second)]
CODE;

    $html = lensHtml($code);

    expect(substr_count($html, "class='codelens'"))->toBe(2)
        ->and($html)->toContain(
            "<span class='codelens-item'>first</span>",
            "<span class='codelens-item'>second</span>"
        );
});

test('block gets has-codelens class', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(test)]
CODE;

    $html = lensHtml($code);

    expect($html)->toContain('has-codelens');
});

test('lens on different lines renders above correct lines', function (): void {
    $code = <<<'CODE'
$a = 1;
$b = 2; // [tl! lens(Second)]
$c = 3;
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("class='codelens'", "<span class='codelens-item'>Second</span>");

    $firstLineContent = '$a = 1;';
    $firstLinePos = strpos($html, htmlspecialchars($firstLineContent));
    $codelensPos = strpos($html, "class='codelens'");

    expect($codelensPos)->toBeGreaterThan($firstLinePos);
});

test('empty lens does nothing', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens()]
CODE;

    $html = lensHtml($code);

    expect($html)->not->toContain('codelens');
});

test('lens content is html escaped', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(<script>alert(1)</script>)]
CODE;

    $html = lensHtml($code);

    expect($html)->not->toContain('<script>');
    expect($html)->toContain('&lt;script&gt;');
});

test('lens works in plain text', function (): void {
    $code = <<<'CODE'
hello world [tl! lens(Above)]
CODE;

    $html = lensHtml($code, 'txt');

    expect($html)->toContain("class='codelens'", 'Above');
});

test('codelens includes line-number spacer when gutter is enabled', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(Above)]
$b = 2;
CODE;

    $html = lensHtml($code);

    expect($html)->toMatch("/<div class='codelens'><span[^>]*class=\"line-number\">[^<]*<\/span>/");
});

test('codelens has no spacer when gutter is disabled', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(Above)]
CODE;

    $html = lensHtmlNoGutter($code);

    expect($html)->toContain("class='codelens'")
        ->and($html)->not->toMatch("/<div class='codelens'><span[^>]*class=\"line-number\"/");
});

test('codelens spacer width matches line count', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(A)]
$b = 2;
CODE;

    $html = lensHtml($code);

    expect($html)->toMatch("/<div class='codelens'><span[^>]*class=\"line-number\"> <\/span>/");
});

test('codelens spacer width grows with line count', function (): void {
    $lines = [];
    for ($i = 1; $i <= 11; $i++) {
        $lines[] = '$x = '.$i.';';
    }
    $lines[0] .= ' // [tl! lens(Wide)]';
    $code = implode("\n", $lines);

    $html = lensHtml($code);

    expect($html)->toMatch("/<div class='codelens'><span[^>]*class=\"line-number\">  <\/span>/");
});

test('codelens includes diff spacer when diff gutter is separate', function (): void {
    $code = <<<'CODE'
// torchlight! {"diffIndicatorsInPlaceOfLineNumbers": false}
$a = 1; // [tl! lens(Above) add]
$b = 2;
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("class='codelens'")
        ->and($html)->toMatch("/class='codelens'.*?class=\"line-number\"/s")
        ->and($html)->toMatch("/class='codelens'.*?class=\"diff-indicator diff-indicator-empty\"/s");
});

test('codelens includes nbsp for indented code', function (): void {
    $code = <<<'CODE'
function foo() {
    $a = 1; // [tl! lens(Indented)]
}
CODE;

    $html = lensHtml($code);

    expect($html)->toMatch("/class='codelens'>.*?&nbsp;&nbsp;&nbsp;&nbsp;<span class='codelens-item'>Indented/");
});

test('codelens has no nbsp for non-indented code', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! lens(NoIndent)]
CODE;

    $html = lensHtml($code);

    expect($html)->not->toMatch("/class='codelens'>.*?&nbsp;.*?codelens-item/s");
});

test('codelens nbsp count matches deeper indentation', function (): void {
    $code = <<<'CODE'
function foo() {
    if (true) {
        $a = 1; // [tl! lens(Deep)]
    }
}
CODE;

    $html = lensHtml($code);

    expect($html)->toMatch("/class='codelens'>.*?".str_repeat('&nbsp;', 8)."<span class='codelens-item'>Deep/");
});

function lensHtmlWithGuides(string $code, string $mode = 'html', string $grammar = 'php', string $theme = 'github-light'): string
{
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, indentGuides: $mode));

    return $engine->codeToHtml($code, $grammar, $theme);
}

test('codelens uses guide spans when html indent guides are active', function (): void {
    $code = <<<'CODE'
function foo() {
    $a = 1; // [tl! lens(Indented)]
}
CODE;

    $html = lensHtmlWithGuides($code, 'html');

    expect($html)->toMatch("/class='codelens'>.*?tl-guide.*?codelens-item/s")
        ->and($html)->toContain('tl-guide-d1');
});

test('codelens uses ascii guide spans when ascii indent guides are active', function (): void {
    $code = <<<'CODE'
function foo() {
    $a = 1; // [tl! lens(Indented)]
}
CODE;

    $html = lensHtmlWithGuides($code, 'ascii');

    expect($html)->toMatch("/class='codelens'>.*?tl-guide.*?codelens-item/s")
        ->and($html)->toContain('│');
});

test('codelens guide spans have no nbsp when guides are active', function (): void {
    $code = <<<'CODE'
function foo() {
    $a = 1; // [tl! lens(Indented)]
}
CODE;

    $html = lensHtmlWithGuides($code, 'html');

    preg_match("/class='codelens'>(.*?)<span class='codelens-item/s", $html, $m);
    expect($m[1] ?? '')->not->toContain('&nbsp;');
});

test('codelens deeper indentation gets multiple guide levels', function (): void {
    $code = <<<'CODE'
function foo() {
    if (true) {
        $a = 1; // [tl! lens(Deep)]
    }
}
CODE;

    $html = lensHtmlWithGuides($code, 'html');

    expect($html)->toContain('tl-guide-d1', 'tl-guide-d2');
});

test('codelens auto-detects tab width to match code guide levels', function (): void {
    $code = <<<'CODE'
function foo() {
  if (true) {
    $a = 1; // [tl! lens(Match)]
  }
}
CODE;

    $html = lensHtmlWithGuides($code, 'html');

    preg_match("/class='codelens'>(.*?)<span class='codelens-item/s", $html, $m);
    $codelensContent = $m[1] ?? '';

    expect($codelensContent)->toContain('tl-guide-d1', 'tl-guide-d2');
});

test('lens works with word-diff annotation on same line', function (): void {
    $code = <<<'CODE'
class Greeter
{
    public function greet($person)
    {
        return "Goodbye, {$person}!";
        return "Hello, {$person}!"; // [tl! wd lens(hello world)]
    }
}
CODE;

    $html = lensHtml($code);

    expect($html)->toContain("class='codelens'", "<span class='codelens-item'>hello world</span>", 'has-word-diff');
});
