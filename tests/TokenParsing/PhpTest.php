<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it parses annotations in php', function () {
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

    $phiki = $this->makeEngine();
    $html = $phiki->codeToHtml($code, 'php', 'github-light');

    $this->assertStringNotContainsString('[tl! remove]', $html);
    $this->assertStringNotContainsString('[tl! add]', $html);

    $annotations = $phiki->getParsedAnnotations();

    $this->assertCount(2, $annotations);

    $remove = $annotations[0];

    $this->assertSame('remove', $remove->name);
    $this->assertSame('remove', $remove->text);
    $this->assertSame(0, $remove->index);
    $this->assertSame(7, $remove->sourceLine);

    $add = $annotations[1];

    $this->assertSame('add', $add->name);
    $this->assertSame('add', $add->text);
    $this->assertSame(1, $add->index);
    $this->assertSame(8, $add->sourceLine);
});
