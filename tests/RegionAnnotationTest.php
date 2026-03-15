<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('region annotation wraps lines in a named div', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    use App\Models\User; // [tl! region("imports")]
    use App\Services\Auth;
    // end region // [tl! region("imports") end]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'data-region="imports"',
        'has-regions',
        '<div class="tl-region" data-region="imports">',
        '</div>',
    );
});

test('region annotation adds data-region attribute to start line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $x = 1; // [tl! region("setup")]
    $y = 2;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain("data-region='setup'");
});

test('region annotation in plain text', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = "Start [tl! region(\"section1\")]\nMiddle\nEnd [tl! region(\"section1\") end]";

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain(
        '<div class="tl-region" data-region="section1">',
        '</div>',
        'has-regions'
    );
});

test('region with default name', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $x = 1; // [tl! region]
    $y = 2;
    $z = 3; // [tl! region end]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        '<div class="tl-region" data-region="default">',
        '</div>'
    );
});
