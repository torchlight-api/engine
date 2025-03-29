<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it adds basic class names to lines', function () {
    $code = <<<'PHP'
return [
    'extensions' => [
        // Add attributes straight from markdown.
        AttributesExtension::class,

        // Add Torchlight syntax highlighting. [tl! highlight .animate-pulse]
        TorchlightExtension::class, // [tl! highlight .font-bold .italic .animate-pulse #pulse]
    ]
]
PHP;

    $results = $this->toParsedResult($code);

    $this->assertTrue($results->line(6)->hasClass('animate-pulse'));
    $this->assertTrue($results->line(6)->isHighlighted());
    $this->assertTrue($results->line(6)->hasBackground());
    $this->assertSame('', $results->line(6)->id);

    $this->assertSame('pulse', $results->line(7)->id);
    $this->assertTrue($results->line(7)->isHighlighted());
    $this->assertTrue($results->line(7)->hasBackground());
    $this->assertTrue($results->line(7)->hasClass('font-bold'));
    $this->assertTrue($results->line(7)->hasClass('italic'));
    $this->assertTrue($results->line(7)->hasClass('animate-pulse'));
});

test('it supports tailwind JIT syntax', function () {
    $code = <<<'TEXT'
ID only                   // [tl! #id]
ID + Class                // [tl! #id.pt-4]
Negative Tailwind classes // [tl! .-pt-4 .pb-8]
ID + Classes Mixed        // [tl! .-pt-4#id1.pb-8]
Tailwind Prefixes         // [tl! .sm:pb-8]
Tailwind JIT              // [tl! .sm:pb-[calc(8px-4px)]]
Tailwind JIT              // [tl! .pr-[8px]]
Tailwind JIT + ID         // [tl! .-pt-4.pb-8.pr-[8px] #id]
TEXT;

    $results = $this->toParsedResult($code, 'text');

    $this->assertSame('id', $results->line(1)->id);

    $this->assertSame('id', $results->line(2)->id);
    $this->assertTrue($results->line(2)->hasClass('pt-4'));

    $this->assertTrue($results->line(3)->hasClass('-pt-4'));
    $this->assertTrue($results->line(3)->hasClass('pb-8'));

    $this->assertSame('id1', $results->line(4)->id);
    $this->assertTrue($results->line(4)->hasClass('-pt-4'));
    $this->assertTrue($results->line(4)->hasClass('pb-8'));

    $this->assertTrue($results->line(5)->hasClass('sm:pb-8'));

    $this->assertTrue($results->line(6)->hasClass('sm:pb-[calc(8px-4px)]'));

    $this->assertTrue($results->line(7)->hasClass('pr-[8px]'));

    $this->assertSame('id', $results->line(8)->id);
    $this->assertTrue($results->line(8)->hasClass('-pt-4'));
    $this->assertTrue($results->line(8)->hasClass('pb-8'));
    $this->assertTrue($results->line(8)->hasClass('pr-[8px]'));
});

test('tailwind classes with colons can be used with start/end ranges', function () {
    $code = <<<'TEXT'
One // [tl! .sm:pb-8:start]
Two
Three
Four
Five
Six // [tl! .sm:pb-8:end]
Seven
Eight
Nine
Ten
TEXT;

    $result = $this->toParsedResult($code, 'text');

    for ($i = 1; $i <= 6; $i++) {
        $this->assertTrue($result->line($i)->hasClass('sm:pb-8'));
    }

    for ($i = 7; $i <= 10; $i++) {
        $this->assertFalse($result->line($i)->hasClass('sm:pb-8'));
    }
});

test('add classes numeric ranges', function () {
    $code = <<<'TEXT'
One
Two
Three
Four
Five
Six //  [tl! .-pt-4 .sm:pb-[calc(8px-4px)]:-3,1]
Seven
Eight
Nine
Ten
TEXT;

    $result = $this->toParsedResult($code, 'text');

    $this->assertFalse($result->line(1)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(2)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertTrue($result->line(3)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(4)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(5)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(6)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(7)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(8)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(9)->hasClass('sm:pb-[calc(8px-4px)]'));
    $this->assertFalse($result->line(10)->hasClass('sm:pb-[calc(8px-4px)]'));
});
