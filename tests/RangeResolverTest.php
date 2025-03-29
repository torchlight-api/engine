<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

test('it resolves simple relative ranges', function () {
    $lines = [
        // Start 3 lines down from 2, and extend cover 2 lines total.
        ['// [tl! add:3,2]', 2],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $this->assertCount(1, $annotations);

    $add = $annotations[0];
    $range = $this->getImpactedRange($annotations, $add, 10);

    $this->assertNotNull($range);
    $this->assertFalse($range->isSingleLine);
    $this->assertSame(5, $range->startLine);
    $this->assertSame(6, $range->endLine);
});

test('it resolves start/end ranges', function () {
    $lines = [
        ['// [tl! highlight:start]', 5],
        ['// [tl! highlight:end]', 15],
    ];

    $annotations = $this->parseLineAnnotations($lines);

    $highlightStart = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlightStart, 20);
    $this->assertNotNull($range);
    $this->assertFalse($range->isSingleLine);
    $this->assertSame(5, $range->startLine);
    $this->assertSame(15, $range->endLine);
});

test('it uses max line number if no end annotation is found', function () {
    $lines = [
        ['// [tl! highlight:start]', 5],
    ];

    $annotations = $this->parseLineAnnotations($lines);

    $highlightStart = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlightStart, 20);
    $this->assertNotNull($range);
    $this->assertFalse($range->isSingleLine);
    $this->assertSame(5, $range->startLine);
    $this->assertSame(20, $range->endLine);
});

test('it resolves negative ranges', function () {
    $lines = [
        // Start one line up, highlight 10 lines total
        ['// [tl! highlight:-1,10]', 10],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 20);
    $this->assertSame(9, $range->startLine);
    $this->assertSame(18, $range->endLine);
});

test('it resets to one if negative range under flows', function () {

    $lines = [
        ['// [tl! highlight:-5,10]', 1],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 20);
    $this->assertSame(1, $range->startLine);
    $this->assertSame(5, $range->endLine);
});

test('it resets to max line if range exceeds line count', function () {

    $lines = [
        ['// [tl! highlight:5,10]', 12],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 20);
    $this->assertSame(17, $range->startLine);
    $this->assertSame(20, $range->endLine);
});

test('it returns null if end line would be negative', function () {
    $lines = [
        ['// [tl! highlight:-5,1]', 1],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 20);
    $this->assertNull($range);
});

test('it returns null if start and end exceed maximum line count', function () {
    $lines = [
        ['// [tl! highlight:20,1]', 15],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 20);
    $this->assertNull($range);
});

test('it swaps start and end lines if ending is within range but starting exceeds line count', function () {
    $lines = [
        ['// [tl! highlight:10,-15]', 15],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 20);
    $this->assertSame(9, $range->startLine);
    $this->assertSame(20, $range->endLine);
});

test('it resolves single forward ranges', function () {
    $lines = [
        ['// [tl! highlight:10]', 15],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 50);
    $this->assertSame(15, $range->startLine);
    $this->assertSame(25, $range->endLine);
});

test('it resolves single backward ranges', function () {
    $lines = [
        ['// [tl! highlight:-10]', 15],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 50);
    $this->assertSame(4, $range->startLine);
    $this->assertSame(15, $range->endLine);
});

test('relative ranges with 1 are correctly calculated', function () {
    $lines = [
        ['// [tl! highlight:1]', 5],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 50);
    $this->assertSame(5, $range->startLine);
    $this->assertSame(6, $range->endLine);
});

test('relative ranges with 2 are correctly calculated', function () {
    $lines = [
        ['// [tl! highlight:2]', 5],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 50);
    $this->assertSame(5, $range->startLine);
    $this->assertSame(7, $range->endLine);
});

test('ranges in middle are correctly calculated', function () {
    $lines = [
        ['// [tl! highlight:3]', 4],
    ];

    $annotations = $this->parseLineAnnotations($lines);
    $highlight = $annotations[0];

    $range = $this->getImpactedRange($annotations, $highlight, 50);
    $this->assertSame(4, $range->startLine);
    $this->assertSame(7, $range->endLine);
});

test('range all modifier', function () {
    $code = <<<'PHP'
return [
    'one',
    'two',
    'three',
    'four',
    'five', // [tl! highlight:all]
];
PHP;

    $results = $this->toParsedResult($code);

    foreach ($results->lines() as $line) {
        $this->assertTrue($line->isHighlighted());
        $this->assertTrue($line->hasBackground());
    }
});
