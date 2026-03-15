<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('mark annotation highlights text within a line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$result = array_map($fn, $items); // [tl! mark("array_map")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('char-mark', 'has-mark-lines');
});

test('mark annotation with no match does nothing', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! mark("nonexistent")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('char-mark', 'has-mark-lines');
});

test('mark annotation with empty text does nothing', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! mark]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('char-mark');
});

test('mark annotation with all option highlights multiple matches', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$a = $x + $x; // [tl! mark("$x") all]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('has-mark-lines')
        ->and(substr_count($html, 'char-mark'))->toBeGreaterThanOrEqual(2);
});

test('mark annotation in plain text', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = 'Hello world foo bar // [tl! mark("world")]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('char-mark', 'has-mark-lines');
});
