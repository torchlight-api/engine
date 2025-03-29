<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Ranges\RangeType;

test('it parses basic class annotations', function () {
    $results = $this->parseAnnotations('// [tl! .font-bold .italic .animate-pulse]');

    $this->assertCount(3, $results->annotations);

    $fontBold = $results->annotations[0];
    $this->assertSame('.font-bold', $fontBold->name);
    $this->assertSame('.font-bold', $fontBold->text);
    $this->assertSame([], $fontBold->options);
    $this->assertNull($fontBold->range);
    $this->assertSame(AnnotationType::ClassName, $fontBold->type);

    $italic = $results->annotations[1];
    $this->assertSame('.italic', $italic->name);
    $this->assertSame('.italic', $italic->text);
    $this->assertSame([], $italic->options);
    $this->assertNull($italic->range);
    $this->assertSame(AnnotationType::ClassName, $italic->type);

    $animatePulse = $results->annotations[2];
    $this->assertSame('.animate-pulse', $animatePulse->name);
    $this->assertSame('.animate-pulse', $animatePulse->text);
    $this->assertSame([], $animatePulse->options);
    $this->assertNull($animatePulse->range);
    $this->assertSame(AnnotationType::ClassName, $animatePulse->type);
});

test('it parses class annotations with ranges', function () {
    $results = $this->parseAnnotations('// [tl! .font-bold:3,2]');

    $this->assertCount(1, $results->annotations);

    $fontBold = $results->annotations[0];
    $this->assertSame('.font-bold', $fontBold->name);
    $this->assertSame('.font-bold:3,2', $fontBold->text);
    $this->assertSame([], $fontBold->options);
    $this->assertSame(AnnotationType::ClassName, $fontBold->type);
    $this->assertNotNull($fontBold->range);
    $this->assertSame(RangeType::Relative, $fontBold->range->type);
    $this->assertSame('3', $fontBold->range->start);
    $this->assertSame('2', $fontBold->range->end);
});

test('it class and id annotations', function () {
    $results = $this->parseAnnotations('// [tl! .font-bold .italic .animate-pulse #pulse]');

    $this->assertCount(4, $results->annotations);

    $fontBold = $results->annotations[0];
    $this->assertSame('.font-bold', $fontBold->name);
    $this->assertSame('.font-bold', $fontBold->text);
    $this->assertSame([], $fontBold->options);
    $this->assertNull($fontBold->range);
    $this->assertSame(AnnotationType::ClassName, $fontBold->type);

    $italic = $results->annotations[1];
    $this->assertSame('.italic', $italic->name);
    $this->assertSame('.italic', $italic->text);
    $this->assertSame([], $italic->options);
    $this->assertNull($italic->range);
    $this->assertSame(AnnotationType::ClassName, $italic->type);

    $animatePulse = $results->annotations[2];
    $this->assertSame('.animate-pulse', $animatePulse->name);
    $this->assertSame('.animate-pulse', $animatePulse->text);
    $this->assertSame([], $animatePulse->options);
    $this->assertNull($animatePulse->range);
    $this->assertSame(AnnotationType::ClassName, $animatePulse->type);

    $pulseId = $results->annotations[3];
    $this->assertSame('#pulse', $pulseId->name);
    $this->assertSame('#pulse', $pulseId->text);
    $this->assertSame([], $pulseId->options);
    $this->assertNull($pulseId->range);
    $this->assertSame(AnnotationType::IdAttribute, $pulseId->type);
});
