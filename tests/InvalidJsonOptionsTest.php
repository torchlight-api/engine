<?php

use Torchlight\Engine\Exceptions\InvalidJsonException;
use Torchlight\Engine\Tests\TorchlightTestCase;

uses(TorchlightTestCase::class);

test('invalid json option throws exception', function (): void {
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

    $this->expectException(InvalidJsonException::class);
    $this->expectExceptionMessage('Syntax error when parsing options ["lineNumbers": false, asdfasdfasfdasdfasdf].');

    $this->toHtml($code);
});
