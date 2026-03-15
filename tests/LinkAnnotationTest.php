<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('link annotation wraps line content in anchor', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$key = env("APP_KEY"); // [tl! link("#config-key")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-links',
        'tl-link',
        '#config-key'
    );
});

test('link annotation with empty href does nothing', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! link]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('has-links', 'tl-link');
});

test('link annotation with character range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$key = env("APP_KEY"); // [tl! link("#config"):c7,9]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('tl-link', 'has-links');
});

test('link annotation in plain text', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = 'See config section [tl! link("#config")]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain(
        'has-links',
        'tl-link',
        '#config'
    );
});

test('link annotation with URL', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = 'Documentation [tl! link("https://example.com")]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain(
        'https://example.com',
        'tl-link'
    );
});
