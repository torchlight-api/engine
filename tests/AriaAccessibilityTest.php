<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('ARIA attributes added when enabled', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, ariaEnabled: true));

    $html = $engine->codeToHtml('echo "hello";', 'php', 'nord');

    expect($html)->toContain("role='region'", "tabindex='0'", "aria-label='Code block: php'");
});

test('ARIA attributes not present when disabled', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, ariaEnabled: false));

    $html = $engine->codeToHtml('echo "hello";', 'php', 'nord');

    expect($html)->not->toContain("role='region'", 'tabindex=', 'aria-label=');
});

test('ARIA disabled by default to preserve backward compatibility', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $html = $engine->codeToHtml('echo "hello";', 'php', 'nord');

    expect($html)->not->toContain("role='region'");
});

test('ARIA label uses vanity label when available', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, ariaEnabled: true));

    // php-html has a vanity label of 'php'
    $html = $engine->codeToHtml('<?php echo "hello"; ?>', 'php', 'nord');

    expect($html)->toContain("aria-label='Code block: php'");
});

test('ARIA label uses grammar name when no vanity label', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, ariaEnabled: true));

    $html = $engine->codeToHtml('const x = 1;', 'javascript', 'nord');

    expect($html)->toContain("aria-label='Code block: javascript'");
});

test('ARIA generic label for unknown grammar with fallback', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, ariaEnabled: true));

    // Unknown grammar falls back to plaintext but preserves original as vanity
    $html = $engine->codeToHtml('some code', 'fortnite', 'nord');

    expect($html)->toContain("aria-label='Code block: fortnite'");
});

test('ARIA can be enabled via block options', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));

    $code = "// torchlight! {\"ariaEnabled\": true}\necho \"hello\";";

    $html = $engine->codeToHtml($code, 'php', 'nord');

    expect($html)->toContain("role='region'", 'aria-label=');
});

test('ARIA with renderBlock returns attributes in block object', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, ariaEnabled: true));

    $block = $engine->renderCode('echo "hello";', 'php', 'nord');

    expect($block->attributes)->toHaveKey('role', 'region');
    expect($block->attributes)->toHaveKey('tabindex', '0');
    expect($block->attributes)->toHaveKey('aria-label');
});
