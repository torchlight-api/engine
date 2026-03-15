<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('closure annotation adds class to line', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->registerAnnotation('important', function ($ctx): void {
        $ctx->addBlockClass('has-important-lines');
        $ctx->addLineClass('line-important');
    });

    $code = '$x = 1; // [tl! important]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-important-lines', 'line-important');
});

test('closure annotation reads method args', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->registerAnnotation('badge', function ($ctx): void {
        $label = $ctx->getMethodArgs() ?? 'default';
        $ctx->addBlockClass('has-badges');
        $ctx->addLineAttribute('data-badge', $label);
    });

    $code = '$x = 1; // [tl! badge("NEW")]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-badges', "data-badge='NEW'");
});

test('closure annotation reads options', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->registerAnnotation('custom', function ($ctx): void {
        $options = $ctx->getOptions();
        if (in_array('bold', $options)) {
            $ctx->addLineClass('line-bold');
        }
        $ctx->addBlockClass('has-custom');
    });

    $code = '$x = 1; // [tl! custom bold]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-custom', 'line-bold');
});

test('closure annotation with character range support', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->registerAnnotation('underline', function ($ctx): void {
        if ($ctx->isCharacterRange()) {
            $ctx->addAttributesToCharacterRange(['class' => 'char-underline']);
        } else {
            $ctx->addLineClass('line-underline');
        }
        $ctx->addBlockClass('has-underlines');
    }, charRanges: true);

    $code = '$x = 1; // [tl! underline]';

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-underlines', 'line-underline');
});

test('closure annotation with range', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->registerAnnotation('review', function ($ctx): void {
        $ctx->addBlockClass('has-review');
        $ctx->addLineClass('line-review');
    });

    $code = <<<'CODE'
    $a = 1; // [tl! review:2]
    $b = 2;
    $c = 3;
    CODE;

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain('has-review')
        ->and(substr_count($html, 'line-review'))->toBeGreaterThanOrEqual(2);
});

test('closure annotation in plain text', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $engine->registerAnnotation('flag', function ($ctx): void {
        $ctx->addBlockClass('has-flags');
        $ctx->addLineAttribute('data-flag', 'true');
    });

    $code = 'important line [tl! flag]';

    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('has-flags', "data-flag='true'");
});
