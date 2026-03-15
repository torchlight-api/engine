<?php

use Torchlight\Engine\Tests\Loaders\CommentCleaningLoader;
use Torchlight\Engine\Tests\TorchlightTestCase;

uses(TorchlightTestCase::class);

test('it can handle tokens that represent the entire comment line', function (): void {
    $code = <<<'ABAP'
* Full Line Comment Content: [tl! highlight]
REPORT zhello_world.

START-OF-SELECTION.
  WRITE: 'Hello World'.
ABAP;
    $result = $this->toParsedResult($code, 'abap');

    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());
    $this->assertStringContainsString('* Full Line Comment Content: ', $result->line(1)->text);
    $this->assertStringNotContainsString('[tl! ', $result->line(1)->text);

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }
});

test('it does not leave comments behind in apache', function (): void {
    $code = <<<'APACHE'
# [tl! highlight:1,1]
<Directory "/www/htdocs/example">
    AddType text/example ".exm"
</Directory>
APACHE;

    $result = $this->toParsedResult($code, 'apache');
    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());
    $this->assertStringNotContainsString('#', $result->line(1)->text);

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }
});

test('it does not leave comments behind in asciidoc', function (): void {
    $code = <<<'APACHE'
// [tl! highlight:1,1]
*_bold italic phrase_*

**__b__**old italic le**__tt__**ers
APACHE;

    $result = $this->toParsedResult($code, 'asciidoc');
    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());
    $this->assertStringNotContainsString('#', $result->line(1)->text);

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }
});

test('it does not leave comments behind in bat', function (): void {
    $code = <<<'BAT'
Rem [tl! highlight]
@echo off
echo Hello, World!
pause
BAT;

    $result = $this->toParsedResult($code, 'bat');

    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());
    $this->assertStringNotContainsString('#', $result->line(1)->text);

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }

    $code = <<<'BAT'
:: [tl! highlight]
@echo off
echo Hello, World!
pause
BAT;

    $result = $this->toParsedResult($code, 'bat');

    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());
    $this->assertStringNotContainsString('#', $result->line(1)->text);

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }
});

test('it does not leave comments behind', function (string $language, string $commentStyle, string $code): void {
    $result = $this->toParsedResult($code, $language);
    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());

    foreach (explode('|', $commentStyle) as $commentStylePart) {
        $this->assertStringNotContainsString($commentStylePart, $result->line(1)->text);
    }

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }
})->with(fn () => CommentCleaningLoader::load());
