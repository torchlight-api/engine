<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('invalid json option throws exception', function () {
    $code = <<<'CODE'
// torchlight! {"lineNumbers": false, asdfasdfasfdasdfasdf}
'a';
'b';
'c';
'd';
'e';
'f';
'g';
'h';
CODE;

    $this->expectException(\Torchlight\Engine\Exceptions\InvalidJsonException::class);
    $this->expectExceptionMessage('Syntax error when parsing options ["lineNumbers": false, asdfasdfasfdasdfasdf].');

    $this->toHtml($code);
});
