<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Ranges\RangeType;

test('it parses basic annotations', function () {
    $results = $this->parseAnnotations('// [tl! add highlight]');

    $this->assertSame('// ', $results->text);
    $this->assertCount(2, $results->annotations);

    $add = $results->annotations[0];
    $this->assertSame('add', $add->name);
    $this->assertSame('add', $add->text);
    $this->assertNull($add->range);
    $this->assertSame(AnnotationType::Named, $add->type);
    $this->assertEmpty($add->options);

    $highlight = $results->annotations[1];
    $this->assertSame('highlight', $highlight->name);
    $this->assertSame('highlight', $highlight->text);
    $this->assertNull($highlight->range);
    $this->assertSame(AnnotationType::Named, $highlight->type);
    $this->assertEmpty($highlight->options);
});

test('it parses shorthand annotations', function () {
    $results = $this->parseAnnotations('// [tl! ++]');

    $this->assertSame('// ', $results->text);
    $this->assertCount(1, $results->annotations);

    $add = $results->annotations[0];
    $this->assertSame('++', $add->name);
    $this->assertSame('++', $add->text);
    $this->assertNull($add->range);
    $this->assertSame(AnnotationType::Named, $add->type);
    $this->assertEmpty($add->options);
});

test('it parses annotations with ranges', function () {
    $results = $this->parseAnnotations('// [tl! add:3,2]');

    $this->assertCount(1, $results->annotations);

    $add = $results->annotations[0];
    $this->assertNotNull($add->range);
    $this->assertSame('add', $add->name);
    $this->assertSame('add:3,2', $add->text);
    $this->assertSame('3', $add->range->start);
    $this->assertSame('2', $add->range->end);
    $this->assertSame(RangeType::Relative, $add->range->type);
    $this->assertEmpty($add->options);
});

test('it parses start of open ended range', function () {
    $results = $this->parseAnnotations('// [tl! add:start]');

    $this->assertCount(1, $results->annotations);

    $add = $results->annotations[0];
    $this->assertNotNull($add->range);
    $this->assertSame('add', $add->name);
    $this->assertSame('add:start', $add->text);
    $this->assertNull($add->range->start);
    $this->assertNull($add->range->end);
    $this->assertSame(RangeType::OpenEndedStart, $add->range->type);
    $this->assertEmpty($add->options);
});

test('it parses end of open ended range', function () {
    $results = $this->parseAnnotations('// [tl! add:end]');

    $this->assertCount(1, $results->annotations);

    $add = $results->annotations[0];
    $this->assertNotNull($add->range);
    $this->assertSame('add', $add->name);
    $this->assertSame('add:end', $add->text);
    $this->assertNull($add->range->start);
    $this->assertNull($add->range->end);
    $this->assertSame(RangeType::OpenEndedEnd, $add->range->type);
    $this->assertEmpty($add->options);
});

test('it parses multiple annotations with ranges', function () {
    $results = $this->parseAnnotations('// [tl! add:3,2 highlight:-3,-2]');

    $this->assertCount(2, $results->annotations);

    $add = $results->annotations[0];
    $this->assertNotNull($add->range);
    $this->assertSame('add', $add->name);
    $this->assertSame('add:3,2', $add->text);
    $this->assertSame('3', $add->range->start);
    $this->assertSame('2', $add->range->end);
    $this->assertSame(RangeType::Relative, $add->range->type);
    $this->assertEmpty($add->options);

    $highlight = $results->annotations[1];
    $this->assertNotNull($highlight->range);
    $this->assertSame('highlight', $highlight->name);
    $this->assertSame('highlight:-3,-2', $highlight->text);
    $this->assertSame('-3', $highlight->range->start);
    $this->assertSame('-2', $highlight->range->end);
    $this->assertSame(RangeType::Relative, $highlight->range->type);
    $this->assertEmpty($highlight->options);
});

test('it parses relative following line ranges', function () {
    $results = $this->parseAnnotations('// [tl! add:3]');

    $this->assertCount(1, $results->annotations);

    $add = $results->annotations[0];
    $this->assertNotNull($add->range);
    $this->assertSame('add', $add->name);
    $this->assertSame('add:3', $add->text);
    $this->assertNull($add->range->start);
    $this->assertSame('3', $add->range->end);
    $this->assertSame(RangeType::Relative, $add->range->type);
    $this->assertEmpty($add->options);
});

test('it parses relative preceding line ranges', function () {
    $results = $this->parseAnnotations('// [tl! add:-3]');

    $this->assertCount(1, $results->annotations);

    $add = $results->annotations[0];
    $this->assertNotNull($add->range);
    $this->assertSame('add', $add->name);
    $this->assertSame('add:-3', $add->text);
    $this->assertNull($add->range->start);
    $this->assertSame('-3', $add->range->end);
    $this->assertSame(RangeType::Relative, $add->range->type);
    $this->assertEmpty($add->options);
});

test('it parses annotations with parenthesis', function () {
    $results = $this->parseAnnotations('// [tl! reindex(24)]');

    $this->assertCount(1, $results->annotations);

    $reindex = $results->annotations[0];
    $this->assertSame('reindex', $reindex->name);
    $this->assertSame('reindex(24)', $reindex->text);
    $this->assertSame('24', $reindex->methodArgs);
    $this->assertNull($reindex->range);
    $this->assertSame(AnnotationType::Named, $reindex->type);
    $this->assertEmpty($reindex->options);
});

test('it parses annotations with parenthesis and ranges', function () {
    $results = $this->parseAnnotations('// [tl! reindex(+5):6,1]');

    $this->assertCount(1, $results->annotations);

    $reindex = $results->annotations[0];
    $this->assertSame('reindex', $reindex->name);
    $this->assertSame('reindex(+5):6,1', $reindex->text);
    $this->assertSame('+5', $reindex->methodArgs);
    $this->assertSame(AnnotationType::Named, $reindex->type);

    $this->assertNotNull($reindex->range);
    $this->assertSame(RangeType::Relative, $reindex->range->type);
    $this->assertSame('6', $reindex->range->start);
    $this->assertSame('1', $reindex->range->end);
    $this->assertEmpty($reindex->options);
});

test('it parses relative following lines with parenthesis', function () {

    $results = $this->parseAnnotations('// [tl! reindex(+5):6]');

    $this->assertCount(1, $results->annotations);

    $reindex = $results->annotations[0];
    $this->assertSame('reindex', $reindex->name);
    $this->assertSame('reindex(+5):6', $reindex->text);
    $this->assertSame('+5', $reindex->methodArgs);
    $this->assertSame(AnnotationType::Named, $reindex->type);

    $this->assertNotNull($reindex->range);
    $this->assertSame(RangeType::Relative, $reindex->range->type);
    $this->assertNull($reindex->range->start);
    $this->assertSame('6', $reindex->range->end);
    $this->assertEmpty($reindex->options);
});

test('it parses relative preceding lines with parenthesis', function () {
    $results = $this->parseAnnotations('// [tl! reindex(+5):-6]');

    $this->assertCount(1, $results->annotations);

    $reindex = $results->annotations[0];
    $this->assertSame('reindex', $reindex->name);
    $this->assertSame('reindex(+5):-6', $reindex->text);
    $this->assertSame('+5', $reindex->methodArgs);
    $this->assertSame(AnnotationType::Named, $reindex->type);

    $this->assertNotNull($reindex->range);
    $this->assertSame(RangeType::Relative, $reindex->range->type);
    $this->assertNull($reindex->range->start);
    $this->assertSame('-6', $reindex->range->end);
    $this->assertEmpty($reindex->options);
});

test('it parses different types of method args', function ($arg) {
    $results = $this->parseAnnotations("// [tl! reindex({$arg}):-6]");
    $expectedText = "reindex({$arg}):-6";

    $this->assertCount(1, $results->annotations);

    $reindex = $results->annotations[0];
    $this->assertSame('reindex', $reindex->name);
    $this->assertSame($expectedText, $reindex->text);
    $this->assertSame($arg, $reindex->methodArgs);
    $this->assertSame(AnnotationType::Named, $reindex->type);

    $this->assertNotNull($reindex->range);
    $this->assertSame(RangeType::Relative, $reindex->range->type);
    $this->assertNull($reindex->range->start);
    $this->assertSame('-6', $reindex->range->end);
    $this->assertEmpty($reindex->options);
})->with([
    '-1',
    '+1',
    '5',
    'null',
    '24',
    '+1000',
]);

test('it parses annotations with extra options', function () {
    $results = $this->parseAnnotations('// [tl! collapse:start open]');

    $this->assertCount(1, $results->annotations);

    $collapse = $results->annotations[0];
    $this->assertNotNull($collapse->range);
    $this->assertSame('collapse', $collapse->name);
    $this->assertSame('collapse:start open', $collapse->text);
    $this->assertNull($collapse->range->start);
    $this->assertNull($collapse->range->end);
    $this->assertSame(RangeType::OpenEndedStart, $collapse->range->type);
    $this->assertSame(['open'], $collapse->options);
});

test('it parses annotations with extra options and ranges', function () {
    $results = $this->parseAnnotations('// [tl! collapse:3,2 open]');

    $this->assertCount(1, $results->annotations);

    $collapse = $results->annotations[0];
    $this->assertNotNull($collapse->range);
    $this->assertSame('collapse', $collapse->name);
    $this->assertSame('collapse:3,2 open', $collapse->text);
    $this->assertSame('3', $collapse->range->start);
    $this->assertSame('2', $collapse->range->end);
    $this->assertSame(RangeType::Relative, $collapse->range->type);
    $this->assertSame(['open'], $collapse->options);
});

test('it parses annotations with multiple options', function () {
    $results = $this->parseAnnotations('// [tl! collapse:start open something else here]');

    $this->assertCount(1, $results->annotations);

    $collapse = $results->annotations[0];
    $this->assertNotNull($collapse->range);
    $this->assertSame('collapse', $collapse->name);
    $this->assertSame('collapse:start open something else here', $collapse->text);
    $this->assertNull($collapse->range->start);
    $this->assertNull($collapse->range->end);
    $this->assertSame(RangeType::OpenEndedStart, $collapse->range->type);
    $this->assertSame(['open', 'something', 'else', 'here'], $collapse->options);
});

test('it parses multiple annotations with options', function () {
    $results = $this->parseAnnotations('// [tl! add highlight collapse:start open something else here]');

    $this->assertCount(3, $results->annotations);

    $add = $results->annotations[0];
    $this->assertSame('add', $add->name);
    $this->assertSame('add', $add->text);
    $this->assertNull($add->range);
    $this->assertSame(AnnotationType::Named, $add->type);
    $this->assertEmpty($add->options);

    $highlight = $results->annotations[1];
    $this->assertSame('highlight', $highlight->name);
    $this->assertSame('highlight', $highlight->text);
    $this->assertNull($highlight->range);
    $this->assertSame(AnnotationType::Named, $highlight->type);
    $this->assertEmpty($highlight->options);

    $collapse = $results->annotations[2];
    $this->assertNotNull($collapse->range);
    $this->assertSame('collapse', $collapse->name);
    $this->assertSame('collapse:start open something else here', $collapse->text);
    $this->assertNull($collapse->range->start);
    $this->assertNull($collapse->range->end);
    $this->assertSame(RangeType::OpenEndedStart, $collapse->range->type);
    $this->assertSame(['open', 'something', 'else', 'here'], $collapse->options);
});
