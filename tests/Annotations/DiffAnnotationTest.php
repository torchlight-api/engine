<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it applies add diff annotation', function () {
    $code = <<<'PHP'
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,

        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add]
    ]
]
PHP;

    $results = $this->toParsedResult($code);
    $this->assertSame('-', $results->line(7)->lineNumberContent);
    $this->assertSame('+', $results->line(8)->lineNumberContent);
    $this->assertTrue($results->line(7)->hasClass('line-remove'));
    $this->assertTrue($results->line(8)->hasClass('line-add'));
});
