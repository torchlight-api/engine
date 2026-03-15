<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Torchlight\Engine\CommonMark\Extension;
use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

test('simple string replacement via codeToHtml', function (): void {
    $engine = new Engine;
    $engine->addReplacer('sk_live_abc123', 'YOUR_API_KEY');

    $html = $engine->codeToHtml('$key = "sk_live_abc123";', 'php', 'github-light');

    expect($html)->toContain('YOUR_API_KEY')
        ->and($html)->not->toContain('sk_live_abc123');
});

test('simple string replacement via renderCode', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true));
    $engine->addReplacer('sk_live_abc123', 'YOUR_API_KEY');

    $block = $engine->renderCode('$key = "sk_live_abc123";', 'php', 'github-light');

    expect($block->code)->toContain('YOUR_API_KEY')
        ->and($block->code)->not->toContain('sk_live_abc123');
});

test('multiple string replacements via addReplacers', function (): void {
    $engine = new Engine;
    $engine->addReplacers([
        'sk_live_abc123' => 'YOUR_API_KEY',
        'secret_password' => '***',
    ]);

    $html = $engine->codeToHtml('$key = "sk_live_abc123"; $pw = "secret_password";', 'php', 'github-light');

    expect($html)->toContain('YOUR_API_KEY', '***');
    expect($html)->not->toContain('sk_live_abc123', 'secret_password');
});

test('closure-based replacer', function (): void {
    $engine = new Engine;
    $engine->addReplacer(fn (string $html): string => preg_replace('/sk_live_\w+/', 'REDACTED', $html));

    $html = $engine->codeToHtml('$key = "sk_live_anything_here";', 'php', 'github-light');

    expect($html)->toContain('REDACTED')
        ->and($html)->not->toContain('sk_live_anything_here');
});

test('invokable class replacer', function (): void {
    $replacer = new class
    {
        public function __invoke(string $html): string
        {
            return str_replace('SECRET', 'HIDDEN', $html);
        }
    };

    $engine = new Engine;
    $engine->addReplacer($replacer);

    $html = $engine->codeToHtml('echo "SECRET";', 'php', 'github-light');

    expect($html)->toContain('HIDDEN')
        ->and($html)->not->toContain('SECRET');
});

test('addReplacers with mixed string pairs and callables', function (): void {
    $engine = new Engine;
    $engine->addReplacers([
        'find_me' => 'FOUND',
        fn (string $html): string => str_replace('also_find', 'ALSO_FOUND', $html),
    ]);

    $html = $engine->codeToHtml('$a = "find_me"; $b = "also_find";', 'php', 'github-light');

    expect($html)->toContain('FOUND', 'ALSO_FOUND');
});

test('string replacers run before callable replacers', function (): void {
    $engine = new Engine;

    $engine->addReplacer('STEP1', 'STEP2');

    $engine->addReplacer(fn (string $html): string => str_replace('STEP2', 'FINAL', $html));

    $html = $engine->codeToHtml('echo "STEP1";', 'php', 'github-light');

    expect($html)->toContain('FINAL');
    expect($html)->not->toContain('STEP1', 'STEP2');
});

test('replacers persist across multiple codeToHtml calls', function (): void {
    $engine = new Engine;
    $engine->addReplacer('SECRET', 'HIDDEN');

    $html1 = $engine->codeToHtml('echo "SECRET";', 'php', 'github-light');
    $html2 = $engine->codeToHtml('echo "SECRET";', 'php', 'github-light');

    expect($html1)->toContain('HIDDEN')
        ->and($html2)->toContain('HIDDEN');
});

test('clearReplacers removes all replacers', function (): void {
    $engine = new Engine;
    $engine->addReplacer('SECRET', 'HIDDEN');
    $engine->addReplacer(fn ($html) => str_replace('OTHER', 'GONE', $html));
    $engine->clearReplacers();

    $html = $engine->codeToHtml('echo "SECRET OTHER";', 'php', 'github-light');

    expect($html)->toContain('SECRET', 'OTHER');
});

test('CommonMark extension accepts string replacers', function (): void {
    $env = (new Environment)
        ->addExtension(new CommonMarkCoreExtension)
        ->addExtension(new Extension(
            theme: 'github-light',
            replacers: [
                'sk_live_abc123' => 'YOUR_API_KEY',
            ],
        ));

    $converter = new MarkdownConverter($env);
    $html = $converter->convert("```php\n\$key = \"sk_live_abc123\";\n```")->getContent();

    expect($html)->toContain('YOUR_API_KEY')
        ->and($html)->not->toContain('sk_live_abc123');
});

test('CommonMark extension accepts callable replacers', function (): void {
    $env = (new Environment)
        ->addExtension(new CommonMarkCoreExtension)
        ->addExtension(new Extension(
            theme: 'github-light',
            replacers: [
                fn (string $html): string => str_replace('SECRET', 'REDACTED', $html),
            ],
        ));

    $converter = new MarkdownConverter($env);
    $html = $converter->convert("```php\necho \"SECRET\";\n```")->getContent();

    expect($html)->toContain('REDACTED')
        ->and($html)->not->toContain('SECRET');
});

test('CommonMark extension replacers run before renderCallbacks', function (): void {
    $callbackSawReplacedValue = false;

    $env = (new Environment)
        ->addExtension(new CommonMarkCoreExtension)
        ->addExtension(new Extension(
            theme: 'github-light',
            renderCallbacks: [
                function (string $html) use (&$callbackSawReplacedValue) {
                    if (str_contains($html, 'REPLACED')) {
                        $callbackSawReplacedValue = true;
                    }

                    return $html;
                },
            ],
            replacers: [
                'ORIGINAL' => 'REPLACED',
            ],
        ));

    $converter = new MarkdownConverter($env);
    $converter->convert("```php\necho \"ORIGINAL\";\n```")->getContent();

    expect($callbackSawReplacedValue)->toBeTrue();
});
