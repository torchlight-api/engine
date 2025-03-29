<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it inserts links', function () {
    $code = <<<'PHP'
/**
 * @see https://bit.ly/2UMUsiu. [tl! autolink]
 */
$link = 'https://torchlight.dev' // [tl! autolink]
PHP;

    $result = $this->toHtml($code);

    $this->assertStringContainsString(
        '<a href="https://bit.ly/2UMUsiu" target="_blank" rel="noopener" class="torchlight-link" style="color: #6a737d;">https://bit.ly/2UMUsiu</a>',
        $result,
    );

    $this->assertStringContainsString(
        '<a href="https://torchlight.dev" target="_blank" rel="noopener" class="torchlight-link" style="color: #032f62;">https://torchlight.dev</a>',
        $result,
    );
});
