<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Generators\Gutters\CustomContentGutter;
use Torchlight\Engine\Options;

test('gutter annotation renders content in the gutter', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $x = 1; // [tl! gutter("!")]
    $y = 2; // [tl! gutter("TODO")]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('class="custom-gutter"', 'has-custom-gutter', htmlspecialchars('!   '), htmlspecialchars('TODO'));
});

test('gutter annotation with empty content does not render gutter column', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $x = 1; // [tl! gutter]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('class="custom-gutter"')
        ->and($html)->toContain('has-custom-gutter');
});

test('gutter annotation in plain text', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = 'Important line [tl! gutter("*")]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain(
        'class="custom-gutter"',
        'has-custom-gutter',
        '*'
    );
});

test('gutter annotation with range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1; // [tl! gutter(">>"):2]
    $b = 2;
    $c = 3;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect(substr_count($html, 'class="custom-gutter"'))->toBeGreaterThanOrEqual(2);
    expect($html)->toContain('&gt;&gt;');
});

test('gutter annotation does not render without withGutter option', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false));

    $code = '$x = 1; // [tl! gutter("!")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('class="custom-gutter"');
});

test('gutter priority ordering can be changed', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->setGutterPriority('custom-content', 50);

    $code = '$x = 1; // [tl! gutter("!")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    $customPos = strpos($html, 'class="custom-gutter"');
    $lineNumPos = strpos($html, 'class="line-number"');

    expect($customPos)->toBeLessThan($lineNumPos);
});

test('gutter annotation targets a named gutter', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1; // [tl! gutter(">>", "nav")]
    $b = 2;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('class="custom-gutter"', '&gt;&gt;')
        ->and($engine->hasGutter('nav'))->toBeTrue();
});

test('gutter annotation with multiple named gutters renders separate columns', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = <<<'CODE'
    $a = 1; // [tl! gutter("!", "status") gutter(">>", "nav")]
    $b = 2;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($engine->hasGutter('status'))->toBeTrue()
        ->and($engine->hasGutter('nav'))->toBeTrue()
        ->and(substr_count($html, 'class="custom-gutter"'))->toBeGreaterThanOrEqual(2);
});

test('user can register a custom gutter and target it via annotation', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $gutter = new CustomContentGutter;
    $gutter->setPriority(50);
    $engine->addGutter('my-custom', $gutter);

    $code = '$x = 1; // [tl! gutter(">>>", "my-custom")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('class="custom-gutter"', '&gt;&gt;&gt;');

    $customPos = strpos($html, 'class="custom-gutter"');
    $lineNumPos = strpos($html, 'class="line-number"');
    expect($customPos)->toBeLessThan($lineNumPos);
});

test('gutter annotation defaults to custom-content when no name specified', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = '$x = 1; // [tl! gutter("!")]';

    $engine->codeToHtml($code, 'php', 'nord');

    expect($engine->hasGutter('custom-content'))->toBeTrue();
});

test('placeGutterAfter positions gutter after the reference', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->placeGutterAfter('custom-content', 'line-numbers');

    $code = '$x = 1; // [tl! gutter("!")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    $lineNumPos = strpos($html, 'class="line-number"');
    $customPos = strpos($html, 'class="custom-gutter"');

    expect($lineNumPos)->toBeLessThan($customPos);
});

test('placeGutterBefore positions gutter before the reference', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->placeGutterBefore('custom-content', 'line-numbers');

    $code = '$x = 1; // [tl! gutter("!")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    $customPos = strpos($html, 'class="custom-gutter"');
    $lineNumPos = strpos($html, 'class="line-number"');

    expect($customPos)->toBeLessThan($lineNumPos);
});

test('placeGutterAfter is a no-op when reference gutter does not exist', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->placeGutterAfter('custom-content', 'nonexistent');

    $code = '$x = 1; // [tl! gutter("!")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('class="custom-gutter"');
});

test('placeGutterAfter works with addGutter for custom positioning', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $gutter = new CustomContentGutter;
    $engine->addGutter('status', $gutter);
    $engine->placeGutterAfter('status', 'diff');

    $code = '$x = 1; // [tl! gutter("OK", "status")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('class="custom-gutter"', 'OK');
});
