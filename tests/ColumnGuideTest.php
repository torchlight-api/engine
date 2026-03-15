<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Generators\ColumnGuideApplicator;
use Torchlight\Engine\Options;

test('column guides are disabled by default', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false));

    $code = "echo 'hello';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('torchlight-colguide', 'has-column-guides');
});

test('column guide at 80 adds guide span and line class', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, columnGuides: [80]));

    $code = "echo 'hello';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)
        ->toContain("class='torchlight-colguide torchlight-colguide-80'")
        ->toContain("style='--col: 80'")
        ->toContain('has-column-guides');
});

test('multiple column guides produce multiple spans', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, columnGuides: [80, 120]));

    $code = "echo 'hello';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)
        ->toContain("class='torchlight-colguide torchlight-colguide-80'")
        ->toContain("class='torchlight-colguide torchlight-colguide-120'")
        ->toContain("style='--col: 80'")
        ->toContain("style='--col: 120'");
});

test('column guides with gutter still work', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, columnGuides: [80]));

    $code = "echo 'hello';\necho 'world';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)
        ->toContain('torchlight-colguide-80')
        ->toContain('has-column-guides');
});

test('block element includes --tl-colguide-max CSS variable', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, columnGuides: [80, 120]));

    $code = "echo 'hello';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('--tl-colguide-max: 120');
});

test('ColumnGuideApplicator computes correct classes', function (): void {
    $classes = ColumnGuideApplicator::computeLineClasses([80]);

    expect($classes)->toBe(['torchlight-colguide-80']);
});

test('ColumnGuideApplicator computes correct guide HTML', function (): void {
    $html = ColumnGuideApplicator::computeGuideHtml([80]);

    expect($html)->toBe("<span class='torchlight-colguide torchlight-colguide-80' style='--col: 80'></span>");
});

test('ColumnGuideApplicator with multiple columns produces multiple spans', function (): void {
    $html = ColumnGuideApplicator::computeGuideHtml([80, 120]);

    expect($html)
        ->toContain("torchlight-colguide-80' style='--col: 80'")
        ->toContain("torchlight-colguide-120' style='--col: 120'");
});

test('ColumnGuideApplicator with empty columns returns empty', function (): void {
    expect(ColumnGuideApplicator::computeLineClasses([]))->toBeEmpty()
        ->and(ColumnGuideApplicator::computeGuideHtml([]))->toBe('');
});

test('column guide spans appear on every line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, columnGuides: [80]));

    $code = "line1\nline2\nline3";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect(substr_count($html, "class='torchlight-colguide torchlight-colguide-80'"))->toBe(3);
});

test('column guides do not inject inline background styles', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, columnGuides: [80]));

    $code = "echo 'hello';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->not->toContain('background-image', 'linear-gradient');
});

test('line elements have column guide classes', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: false, columnGuides: [80, 120]));

    $code = "echo 'hello';";
    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain("class='line torchlight-colguide-80 torchlight-colguide-120'");
});
