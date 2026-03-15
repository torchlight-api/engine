<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('macro annotation expands to component annotations', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('important', ['highlight', '.important-line']);

    $code = <<<'CODE'
    $x = 1; // [tl! important]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-highlight-lines',
        'line-highlight',
        'important-line'
    );
});

test('macro annotation with range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('special', ['highlight', 'focus']);

    $code = <<<'CODE'
    $a = 1; // [tl! special:2]
    $b = 2;
    $c = 3;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-highlight-lines',
        'has-focus-lines'
    );
});

test('macro annotation is registered and resolvable', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('combo', ['highlight', 'focus']);

    $registry = $engine->getAnnotationEngine()->getRegistry();

    expect($registry->resolve('combo'))->not->toBeNull();
});

test('macro can be removed by name', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('temp', ['highlight']);
    $engine->removeAnnotation('temp');

    $registry = $engine->getAnnotationEngine()->getRegistry();

    expect($registry->resolve('temp'))->toBeNull();
});

test('macro with single component works', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('hl', ['highlight']);

    $code = <<<'CODE'
    $x = 1; // [tl! hl]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain(
        'has-highlight-lines',
        'line-highlight',
    );
});

test('macro skips unknown component annotations', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('partial', ['highlight', 'nonexistent']);

    $code = <<<'CODE'
    $x = 1; // [tl! partial]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-highlight-lines');
});

test('macro with prefix components', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('fancy', ['.my-class', '#my-id']);

    $code = <<<'CODE'
    $x = 1; // [tl! fancy]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('my-class', 'my-id');
});

test('macro preserves raw method args for component annotations that need them', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->registerAnnotationMacro('lensy', ['lens']);

    $code = <<<'CODE'
    $x = 1; // [tl! lensy("hello, world")]
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('codelens', 'hello, world');
});
