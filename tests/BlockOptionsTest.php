<?php

use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;
use Torchlight\Engine\Tests\TorchlightTestCase;

uses(TorchlightTestCase::class);

test('it parses block options in php', function (): void {
    $code = <<<'PHP'
// torchlight! {"lineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
PHP;

    $phiki = $this->makeEngine();
    $result = $phiki->codeToHtml($code, 'php', 'github-light');

    $this->assertStringNotContainsString('lineNumbers', $result);
    $options = $phiki->getTorchlightOptions();

    $this->assertFalse($options->lineNumbersEnabled);
});

test('it parses block options in text', function (): void {
    $code = <<<'TEXT'
// torchlight! {"lineNumbers": false}
just
some

text
nothing too
fancy
TEXT;

    $phiki = $this->makeEngine();
    $result = $phiki->codeToHtml($code, 'text', 'github-light');

    $this->assertStringNotContainsString('lineNumbers', $result);
    $options = $phiki->getTorchlightOptions();

    $this->assertFalse($options->lineNumbersEnabled);
});

test('it parses block options in json', function (): void {
    $code = <<<'JSON'
// torchlight! {"lineNumbers": false}
{
    "someJson": "someValue",
}
JSON;

    $phiki = $this->makeEngine();
    $result = $phiki->codeToHtml($code, 'json', 'github-light');

    $this->assertStringNotContainsString('lineNumbers', $result);
    $options = $phiki->getTorchlightOptions();

    $this->assertFalse($options->lineNumbersEnabled);
});

test('multiple block options can be set', function (): void {
    $code = <<<'PHP'
// torchlight! {"lineNumbers": false, "lineNumbersStart": 42, "lineNumbersStyle": "opacity: .5;", "diffIndicators": false, "diffIndicatorsInPlaceOfLineNumbers": false, "summaryCollapsedIndicator": "something new", "torchlightAnnotations": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
PHP;

    $phiki = $this->makeEngine();
    $result = $phiki->codeToHtml($code, 'php', 'github-light');

    $this->assertStringNotContainsString('lineNumbers', $result);
    $options = $phiki->getTorchlightOptions();

    $this->assertFalse($options->lineNumbersEnabled);
    $this->assertSame(42, $options->lineNumbersStart);
    $this->assertSame('opacity: .5;', $options->lineNumbersStyle);
    $this->assertFalse($options->diffIndicatorsEnabled);
    $this->assertFalse($options->diffIndicatorsInPlaceOfNumbers);
    $this->assertSame('something new', $options->summaryCollapsedIndicator);
    $this->assertFalse($options->annotationsEnabled);
});

test('block options are not parsed if not the first line', function (): void {
    $code = <<<'PHP'

// torchlight! {"lineNumbers": false, "lineNumbersStart": 42, "lineNumbersStyle": "opacity: .5;", "diffIndicators": false, "diffIndicatorsInPlaceOfLineNumbers": false, "summaryCollapsedIndicator": "something new", "torchlightAnnotations": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
PHP;

    $results = $this->toParsedResult($code);
    $this->assertStringContainsString('torchlight! ', $results->line(2)->text);
});

test('block options are not parsed if annotations disabled', function (): void {
    $code = <<<'PHP'
// torchlight! {"torchlightAnnotations": false}
// torchlight! {"lineNumbers": false, "lineNumbersStart": 42, "lineNumbersStyle": "opacity: .5;", "diffIndicators": false, "diffIndicatorsInPlaceOfLineNumbers": false, "summaryCollapsedIndicator": "something new", "torchlightAnnotations": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,
        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
PHP;

    $results = $this->toParsedResult($code);
    $this->assertStringContainsString('torchlight! ', $results->line(1)->text);
});

test('hideLines option hides lines', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, hideLines: [[2, 2]]));

    $html = $engine->codeToHtml("line 1\nline 2\nline 3", 'text', 'nord');

    expect($html)->toContain('has-hidden-lines', 'line-elided');
});

test('hideLines option via block options', function (): void {
    $engine = new Engine;

    $code = "// torchlight! {\"hideLines\": [2]}\nline 1\nline 2\nline 3";
    $html = $engine->codeToHtml($code, 'text', 'nord');

    expect($html)->toContain('has-hidden-lines');
});

test('line range options support range syntax', function (): void {
    $engine = new Engine;
    $engine->setTorchlightOptions(new Options(withGutter: true, highlightLines: [[1, 3]]));

    $html = $engine->codeToHtml("a\nb\nc\nd", 'text', 'nord');

    expect(substr_count($html, 'line-highlight'))->toBe(3);
});

test('block options can be set using forward slashes in other languages', function (): void {
    $code = <<<'HTML'
// torchlight! {"lineNumbers": false}
<div>
    <p><span></span></p>
</div>
HTML;

    $phiki = $this->makeEngine();
    $result = $phiki->codeToHtml($code, 'html', 'github-light');

    $this->assertStringNotContainsString('lineNumbers', $result);
    $options = $phiki->getTorchlightOptions();

    $this->assertFalse($options->lineNumbersEnabled);
});
