<?php

use Torchlight\Engine\Engine;

test('line elements separate the style and class attributes with a space', function (): void {
    $code = <<<'CODE'
$a = 1; // [tl! highlight]
$b = 2; // [tl! ++]
$c = 3; // [tl! --]
CODE;

    $html = (new Engine)->codeToHtml($code, 'php', 'github-light');

    // Lines that carry a line style used to render as `style="..."class='...'`
    // (no space), which HTML sanitizers like WordPress' wp_kses reject,
    // stripping the class list from highlighted and diff lines entirely.
    expect($html)->toContain('line-highlight')
        ->and($html)->toContain('line-add')
        ->and($html)->toContain('line-remove')
        ->and($html)->not->toMatch('/["\']class=/');
});
