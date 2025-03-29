<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('manually setting a new number', function () {
    $code = <<<'PHP'
'a';
'b';
'c';
'x'; // [tl! reindex(24)]
'y';
'z';
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('24', $results->line(4)->lineNumberContent);
    $this->assertSame('25', $results->line(5)->lineNumberContent);
    $this->assertSame('26', $results->line(6)->lineNumberContent);
});

test('no number at all', function () {
    $code = <<<'PHP'
'a';
'b';
'c';
// Lots of letters... [tl! reindex(null)]
'x'; // [tl! reindex(24)]
'y';
'z';
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('', $results->line(4)->lineNumberContent);
    $this->assertSame('24', $results->line(5)->lineNumberContent);
    $this->assertSame('25', $results->line(6)->lineNumberContent);
    $this->assertSame('26', $results->line(7)->lineNumberContent);
});

test('not immediately reindexing after clearing a line number', function () {
    $code = <<<'PHP'
'a';
'b';
'c';
// Lots of letters... [tl! reindex(null)]
'x';
'y';
'z';
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('', $results->line(4)->lineNumberContent);
    $this->assertSame('4', $results->line(5)->lineNumberContent);
    $this->assertSame('5', $results->line(6)->lineNumberContent);
    $this->assertSame('6', $results->line(7)->lineNumberContent);
});

test('relative reindex changes', function () {
    $code = <<<'PHP'
// torchlight! {"diffIndicatorsInPlaceOfLineNumbers": false}
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,

        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add reindex(-1)]
    ]
]
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('4', $results->line(4)->lineNumberContent);
    $this->assertSame('5', $results->line(5)->lineNumberContent);
    $this->assertSame('6', $results->line(6)->lineNumberContent);
    $this->assertSame('7', $results->line(7)->lineNumberContent);
    $this->assertSame('7', $results->line(8)->lineNumberContent);
    $this->assertSame('8', $results->line(9)->lineNumberContent);
    $this->assertSame('9', $results->line(10)->lineNumberContent);
});

test('diff and reindex', function () {
    $code = <<<'PHP'
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,

        // Add Torchlight syntax highlighting.
        SomeOtherHighlighter::class, // [tl! remove]
        TorchlightExtension::class, // [tl! add reindex(+1000)]
    ]
]
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('4', $results->line(4)->lineNumberContent);
    $this->assertSame('5', $results->line(5)->lineNumberContent);
    $this->assertSame('6', $results->line(6)->lineNumberContent);
    $this->assertSame('-', $results->line(7)->lineNumberContent);
    $this->assertSame('+', $results->line(8)->lineNumberContent);
    $this->assertSame('1009', $results->line(9)->lineNumberContent);
    $this->assertSame('1010', $results->line(10)->lineNumberContent);
});

test('reindex with range modifiers', function () {
    $code = <<<'PHP'
// This is a long bit of text, hard to reindex the middle. [tl! reindex(+5):6,1]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT; // [tl! highlight:-7,3]
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('4', $results->line(4)->lineNumberContent);
    $this->assertSame('5', $results->line(5)->lineNumberContent);
    $this->assertSame('6', $results->line(6)->lineNumberContent);

    $this->assertSame('12', $results->line(7)->lineNumberContent);
    $this->assertTrue($results->line(7)->hasBackground());
    $this->assertTrue($results->line(7)->isHighlighted());

    $this->assertSame('8', $results->line(8)->lineNumberContent);
    $this->assertTrue($results->line(8)->hasBackground());
    $this->assertTrue($results->line(8)->isHighlighted());

    $this->assertSame('9', $results->line(9)->lineNumberContent);
    $this->assertTrue($results->line(9)->hasBackground());
    $this->assertTrue($results->line(9)->isHighlighted());

    $this->assertSame('10', $results->line(10)->lineNumberContent);
    $this->assertSame('11', $results->line(11)->lineNumberContent);
    $this->assertSame('12', $results->line(12)->lineNumberContent);
    $this->assertSame('13', $results->line(13)->lineNumberContent);
    $this->assertSame('14', $results->line(14)->lineNumberContent);
});

test('removing line numbers in the middle of code blocks', function () {
    $code = <<<'PHP'
// This is a long bit of text, hard to reindex the middle. [tl! reindex(null):5,5]
return <<<EOT
spring sunshine
the smell of waters
from the stars

deep winter
the smell of a crow
from the stars

beach to school
the smell of water
in the sky
EOT; // [tl! highlight:-7,3]
PHP;

    $results = $this->toParsedResult($code);

    $this->assertSame('1', $results->line(1)->lineNumberContent);
    $this->assertSame('2', $results->line(2)->lineNumberContent);
    $this->assertSame('3', $results->line(3)->lineNumberContent);
    $this->assertSame('4', $results->line(4)->lineNumberContent);
    $this->assertSame('5', $results->line(5)->lineNumberContent);
    $this->assertSame('', $results->line(6)->lineNumberContent);

    $this->assertSame('', $results->line(7)->lineNumberContent);
    $this->assertTrue($results->line(7)->hasBackground());
    $this->assertTrue($results->line(7)->isHighlighted());

    $this->assertSame('', $results->line(8)->lineNumberContent);
    $this->assertTrue($results->line(8)->hasBackground());
    $this->assertTrue($results->line(8)->isHighlighted());

    $this->assertSame('', $results->line(9)->lineNumberContent);
    $this->assertTrue($results->line(9)->hasBackground());
    $this->assertTrue($results->line(9)->isHighlighted());

    $this->assertSame('', $results->line(10)->lineNumberContent);
    $this->assertSame('6', $results->line(11)->lineNumberContent);
    $this->assertSame('7', $results->line(12)->lineNumberContent);
    $this->assertSame('8', $results->line(13)->lineNumberContent);
    $this->assertSame('9', $results->line(14)->lineNumberContent);
});
