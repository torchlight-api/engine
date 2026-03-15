<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('hide annotation replaces line with elision placeholder', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    use App\Models\User;
    use App\Models\Post; // [tl! hide]
    use App\Services\Auth;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-hidden-lines',
        'line-elided',
        'tl-elision',
        '...'
    );
});

test('hide annotation with range hides multiple lines', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    use App\Models\User;
    use App\Models\Post; // [tl! hide:2]
    use App\Models\Comment;
    use App\Services\Auth;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-hidden-lines',
        'line-elided',
        'line-hidden',
        "data-hidden-count='3'"
    );
});

test('hide annotation with custom placeholder', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    use App\Models\User;
    use App\Models\Post; // [tl! hide("// ...more imports")]
    use App\Services\Auth;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('// ...more imports', 'tl-elision');
});

test('hide annotation in plain text', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = "line one\nline two [tl! hide]\nline three";

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain(
        'has-hidden-lines',
        'line-elided',
        'tl-elision',
    );
});

test('multiple hide annotations', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    line 1
    line 2 // [tl! hide]
    line 3
    line 4 // [tl! hide]
    line 5
    CODE;

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect(substr_count($html, 'line-elided'))->toBe(2)
        ->and($html)->toContain('has-hidden-lines');
});
