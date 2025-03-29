<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it parses annotations in json', function () {
    $code = <<<'CODE'
{
    "torchlightAnnotations": true,
    "lineNumbers": true, // [tl! focus:2]
    "lineNumbersStart": 1,
    "lineNumbersStyle": "text-align: right; -webkit-user-select: none; user-select: none;",
    "summaryCollapsedIndicator": "...",
 
    "diffIndicators": false,
    "diffIndicatorsInPlaceOfLineNumbers": true,
}
CODE;

    $phiki = $this->makeEngine();
    $html = $phiki->codeToHtml($code, 'json', 'github-light');

    $this->assertStringNotContainsString('[tl! highlight:2]', $html);
    $this->assertStringNotContainsString('//', $html);

    $annotations = $phiki->getParsedAnnotations();

    $this->assertCount(1, $annotations);

    $focus = $annotations[0];

    $this->assertSame('focus', $focus->name);
    $this->assertSame('focus:2', $focus->text);
    $this->assertSame(0, $focus->index);
    $this->assertSame(3, $focus->sourceLine);
});
