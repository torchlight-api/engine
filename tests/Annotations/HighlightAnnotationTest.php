<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it applies highlight annotation', function () {
    $code = <<<'PHP'
return [
    'extensions' => [
        // Add attributes straight from markdown. [tl! highlight:1]
        AttributesExtension::class,

        // Add Torchlight syntax highlighting.
        TorchlightExtension::class,
    ]
]
PHP;

    $result = $this->toParsedResult($code);

    $this->assertFalse($result->line(2)->hasBackground());
    $this->assertFalse($result->line(2)->isHighlighted());

    $this->assertTrue($result->line(3)->hasBackground());
    $this->assertTrue($result->line(4)->hasBackground());
    $this->assertTrue($result->line(3)->isHighlighted());
    $this->assertTrue($result->line(4)->isHighlighted());

    $this->assertFalse($result->line(5)->hasBackground());
    $this->assertFalse($result->line(5)->isHighlighted());
});
