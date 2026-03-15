<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('word diff merges line with wd annotation against the line above', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
    $name = "old"; // [tl! wd]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');
    expect($html)->not->toContain('has-word-diff');
});

test('word diff basic two-line merge', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
    $name = "old";
    $name = "new"; // [tl! wd]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-word-diff', 'word-diff-del', 'word-diff-ins', 'line-word-diff')
        ->and(substr_count($html, "class='line"))->toBe(1);
});

test('word diff physically removes the wd line from output', function (): void {
    $engine = new Engine;

    $code = "old value\nnew value [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect(substr_count($html, "class='line"))->toBe(1)
        ->and($html)->toContain('has-word-diff')
        ->and($html)->not->toContain('line-hidden');
});

test('word diff does not activate when lines are identical', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
    $x = 1;
    $x = 1; // [tl! wd]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('has-word-diff');
});

test('word diff with multiple wd lines', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
    $name = "old";
    $name = "new"; // [tl! wd]
    $age = 25;
    $age = 30; // [tl! wd]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-word-diff')
        ->and(substr_count($html, 'word-diff-del'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($html, 'word-diff-ins'))->toBeGreaterThanOrEqual(2)
        ->and(substr_count($html, "class='line"))->toBe(2);
});

test('word diff multi-region produces multiple del/ins pairs', function (): void {
    $engine = new Engine;

    $code = "\$name1 = \"old\";\n\$name2 = \"new\"; // [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('has-word-diff')
        ->and(substr_count($html, 'word-diff-del'))->toBe(2)
        ->and(substr_count($html, 'word-diff-ins'))->toBe(2)
        ->and(substr_count($html, "class='line"))->toBe(1);
});

test('word diff in plain text', function (): void {
    $engine = new Engine;

    $code = "old value\nnew value [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('word-diff-del', 'word-diff-ins', 'has-word-diff');
});

test('word diff is independent from regular diff', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
    $x = 100;
    $x = 200; // [tl! wd]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('line-add', 'line-remove');
    expect($html)->toContain('word-diff-del', 'word-diff-ins');
});

test('word diff handles sub-word insertion as whole-word replacement', function (): void {
    $engine = new Engine;

    $code = "ab\naXb [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('word-diff-del', 'word-diff-ins', 'has-word-diff');
});

test('word diff handles sub-word deletion as whole-word replacement', function (): void {
    $engine = new Engine;

    $code = "aXb\nab [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('word-diff-del', 'word-diff-ins', 'has-word-diff');
});

test('word diff at start of line', function (): void {
    $engine = new Engine;

    $code = "old_rest\nnew_rest [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('word-diff-del', 'word-diff-ins');
});

test('word diff at end of line', function (): void {
    $engine = new Engine;

    $code = "prefix_old\nprefix_new [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('word-diff-del', 'word-diff-ins');
});

test('word diff del/ins spans wrap correct characters with gutter enabled', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = "old value\nnew value [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toMatch('/<span class="word-diff-del"[^>]*>.*old.*<\/span>/')
        ->and($html)->toMatch('/<span class="word-diff-ins"[^>]*>new<\/span>/')
        ->and($html)->not->toContain('</span>d');
});

test('word diff produces continuous line numbers', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1;
    $name = "old";
    $name = "new"; // [tl! wd]
    $b = 2;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect(substr_count($html, "class='line"))->toBe(3)
        ->and($html)->toContain('>1', '>2', '>3')
        ->and($html)->not->toContain('>4');
});

test('word diff Goodbye to Hello produces clean single replacement', function (): void {
    $engine = new Engine;

    $code = <<<'CODE'
        return "Goodbye, {$person}!";
        return "Hello, {$person}!"; // [tl! wd]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-word-diff')
        ->and($html)->toMatch('/<span class="word-diff-del"[^>]*>.*Goodbye.*<\/span>/')
        ->and($html)->toMatch('/<span class="word-diff-ins"[^>]*>Hello<\/span>/')
        ->and(substr_count($html, "class='line"))->toBe(1);
});

test('word diff multi-region at word level', function (): void {
    $engine = new Engine;

    $code = "\$name1 = \"old\";\n\$name2 = \"new\"; // [tl! wd]";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('has-word-diff')
        ->and(substr_count($html, 'word-diff-del'))->toBe(2)
        ->and(substr_count($html, 'word-diff-ins'))->toBe(2)
        ->and($html)->toMatch('/<span class="word-diff-del"[^>]*>.*name1.*<\/span>/')
        ->and($html)->toMatch('/<span class="word-diff-ins"[^>]*>name2<\/span>/');
});

test('word diff on first line does nothing', function (): void {
    $engine = new Engine;

    $code = 'only line [tl! wd]';
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->not->toContain('has-word-diff', 'word-diff-del', 'word-diff-ins')
        ->and(substr_count($html, "class='line"))->toBe(1);
});
