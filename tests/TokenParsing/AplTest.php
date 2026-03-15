<?php

use Torchlight\Engine\Tests\TorchlightTestCase;

uses(TorchlightTestCase::class);

test('it does not duplicate comment token contents if torchlight annotation is not found', function (): void {
    $code = <<<'APL'
'Hello, world' ⍝ the comment
APL;

    $result = $this->toParsedResult($code, 'apl');

    $this->assertNull($result->line(2));
    $this->assertSame(1, mb_substr_count((string) $result->line(1)->text, 'the comment'));
});
