<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it parses annotations in plain text', function () {
    $code = <<<'CODE'
spring sunshine
the smell of waters
from the stars

deep winter [tl! highlight:2]
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
CODE;

    $phiki = $this->makeEngine();
    $html = $phiki->codeToHtml($code, 'text', 'github-light');

    $this->assertStringNotContainsString('[tl! highlight:2]', $html);

    $annotations = $phiki->getParsedAnnotations();

    $this->assertCount(1, $annotations);

    $highlight = $annotations[0];

    $this->assertSame('highlight', $highlight->name);
    $this->assertSame('highlight:2', $highlight->text);
    $this->assertSame(0, $highlight->index);
    $this->assertSame(5, $highlight->sourceLine);
});
