<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it parses block options in php', function () {
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

test('it parses block options in text', function () {
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

test('it parses block options in json', function () {
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

test('multiple block options can be set', function () {
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

test('block options are not parsed if not the first line', function () {
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

test('block options are not parsed if annotations disabled', function () {
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

test('block options can be set using forward slashes in other languages', function () {
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
