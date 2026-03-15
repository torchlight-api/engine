<?php

use Torchlight\Engine\Annotations\Parser\AnnotationTokenParser;
use Torchlight\Engine\Annotations\Parser\AnnotationType;
use Torchlight\Engine\Annotations\Ranges\RangeType;
use Torchlight\Engine\Tests\TorchlightTestCase;

uses(TorchlightTestCase::class);

test('it parses basic class annotations', function (): void {
    $results = $this->parseAnnotations('// [tl! .font-bold .italic .animate-pulse]');

    $this->assertCount(3, $results->annotations);

    $fontBold = $results->annotations[0];
    $this->assertSame('.font-bold', $fontBold->name);
    $this->assertSame('.font-bold', $fontBold->text);
    $this->assertSame([], $fontBold->options);
    $this->assertNull($fontBold->range);
    $this->assertSame(AnnotationType::Prefixed, $fontBold->type);
    $this->assertSame('.', $fontBold->prefix);

    $italic = $results->annotations[1];
    $this->assertSame('.italic', $italic->name);
    $this->assertSame('.italic', $italic->text);
    $this->assertSame([], $italic->options);
    $this->assertNull($italic->range);
    $this->assertSame(AnnotationType::Prefixed, $italic->type);
    $this->assertSame('.', $italic->prefix);

    $animatePulse = $results->annotations[2];
    $this->assertSame('.animate-pulse', $animatePulse->name);
    $this->assertSame('.animate-pulse', $animatePulse->text);
    $this->assertSame([], $animatePulse->options);
    $this->assertNull($animatePulse->range);
    $this->assertSame(AnnotationType::Prefixed, $animatePulse->type);
    $this->assertSame('.', $animatePulse->prefix);
});

test('it parses class annotations with ranges', function (): void {
    $results = $this->parseAnnotations('// [tl! .font-bold:3,2]');

    $this->assertCount(1, $results->annotations);

    $fontBold = $results->annotations[0];
    $this->assertSame('.font-bold', $fontBold->name);
    $this->assertSame('.font-bold:3,2', $fontBold->text);
    $this->assertSame([], $fontBold->options);
    $this->assertSame(AnnotationType::Prefixed, $fontBold->type);
    $this->assertSame('.', $fontBold->prefix);
    $this->assertNotNull($fontBold->range);
    $this->assertSame(RangeType::Relative, $fontBold->range->type);
    $this->assertSame('3', $fontBold->range->start);
    $this->assertSame('2', $fontBold->range->end);
});

test('it class and id annotations', function (): void {
    $results = $this->parseAnnotations('// [tl! .font-bold .italic .animate-pulse #pulse]');

    $this->assertCount(4, $results->annotations);

    $fontBold = $results->annotations[0];
    $this->assertSame('.font-bold', $fontBold->name);
    $this->assertSame('.font-bold', $fontBold->text);
    $this->assertSame([], $fontBold->options);
    $this->assertNull($fontBold->range);
    $this->assertSame(AnnotationType::Prefixed, $fontBold->type);
    $this->assertSame('.', $fontBold->prefix);

    $italic = $results->annotations[1];
    $this->assertSame('.italic', $italic->name);
    $this->assertSame('.italic', $italic->text);
    $this->assertSame([], $italic->options);
    $this->assertNull($italic->range);
    $this->assertSame(AnnotationType::Prefixed, $italic->type);
    $this->assertSame('.', $italic->prefix);

    $animatePulse = $results->annotations[2];
    $this->assertSame('.animate-pulse', $animatePulse->name);
    $this->assertSame('.animate-pulse', $animatePulse->text);
    $this->assertSame([], $animatePulse->options);
    $this->assertNull($animatePulse->range);
    $this->assertSame(AnnotationType::Prefixed, $animatePulse->type);
    $this->assertSame('.', $animatePulse->prefix);

    $pulseId = $results->annotations[3];
    $this->assertSame('#pulse', $pulseId->name);
    $this->assertSame('#pulse', $pulseId->text);
    $this->assertSame([], $pulseId->options);
    $this->assertNull($pulseId->range);
    $this->assertSame(AnnotationType::Prefixed, $pulseId->type);
    $this->assertSame('#', $pulseId->prefix);
});

test('it prefers the longest registered prefix when parsing', function (): void {
    $parser = new AnnotationTokenParser;
    $parser->setRegisteredPrefixes(['@', '@@']);

    $results = $parser->parseText('[tl! @@region @inline]', 1);

    $this->assertCount(2, $results->annotations);
    $this->assertSame('@@', $results->annotations[0]->prefix);
    $this->assertSame('@@region', $results->annotations[0]->name);
    $this->assertSame('@', $results->annotations[1]->prefix);
    $this->assertSame('@inline', $results->annotations[1]->name);
});
