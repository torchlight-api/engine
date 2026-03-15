<?php

use Phiki\Highlighting\Highlighter;
use Torchlight\Engine\Annotations\AbstractAnnotation;
use Torchlight\Engine\Annotations\AnnotationEngine;
use Torchlight\Engine\Annotations\AnnotationRegistry;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Generators\ThemeStyleResolver;
use Torchlight\Engine\Options;

// Create a simple concrete annotation for testing
class TestAnnotation extends AbstractAnnotation
{
    public static string $name = 'test';

    public static array $aliases = ['t'];

    public bool $highlighterWasSet = false;

    public bool $optionsWereSet = false;

    public bool $themeResolverWasSet = false;

    public function process(ParsedAnnotation $annotation): void
    {
        // No-op for testing
    }

    public function setHighlighter(Highlighter $highlighter): static
    {
        $this->highlighterWasSet = true;

        return parent::setHighlighter($highlighter);
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->optionsWereSet = true;

        return parent::setTorchlightOptions($options);
    }

    public function setThemeResolver(ThemeStyleResolver $resolver): static
    {
        $this->themeResolverWasSet = true;

        return parent::setThemeResolver($resolver);
    }
}

test('register stores annotation by name', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $registry->register('test', $annotation);

    $this->assertSame($annotation, $registry->get('test'));
});

test('get retrieves registered annotation', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $registry->register('myAnnotation', $annotation);

    $this->assertSame($annotation, $registry->get('myAnnotation'));
});

test('get returns null for unregistered', function (): void {
    $registry = new AnnotationRegistry;

    $this->assertNull($registry->get('nonexistent'));
});

test('has returns true for registered annotation', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $registry->register('test', $annotation);

    $this->assertTrue($registry->has('test'));
});

test('has returns false for unregistered', function (): void {
    $registry = new AnnotationRegistry;

    $this->assertFalse($registry->has('nonexistent'));
});

test('registerPrefixHandler stores handler', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $registry->registerPrefixHandler('.', $annotation);

    // Verify via resolve
    $this->assertSame($annotation, $registry->resolve('.my-class'));
});

test('resolve returns prefix handler for matching prefix', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $classAnnotation = new TestAnnotation($processor);
    $idAnnotation = new TestAnnotation($processor);

    $registry->registerPrefixHandler('.', $classAnnotation);
    $registry->registerPrefixHandler('#', $idAnnotation);

    $this->assertSame($classAnnotation, $registry->resolve('.my-class'));
    $this->assertSame($idAnnotation, $registry->resolve('#my-id'));
});

test('resolve prefers the longest matching prefix', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $shortPrefix = new TestAnnotation($processor);
    $longPrefix = new TestAnnotation($processor);

    $registry->registerPrefixHandler('@', $shortPrefix);
    $registry->registerPrefixHandler('@@', $longPrefix);

    $this->assertSame($longPrefix, $registry->resolve('@@region'));
});

test('resolve falls back to normal registry', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $registry->register('highlight', $annotation);

    $this->assertSame($annotation, $registry->resolve('highlight'));
});

test('resolve returns null when not found', function (): void {
    $registry = new AnnotationRegistry;

    $this->assertNull($registry->resolve('nonexistent'));
});

test('all returns only named annotations', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation1 = new TestAnnotation($processor);
    $annotation2 = new TestAnnotation($processor);
    $prefixHandler = new TestAnnotation($processor);

    $registry->register('test1', $annotation1);
    $registry->register('test2', $annotation2);
    $registry->registerPrefixHandler('.', $prefixHandler);

    $all = $registry->all();

    $this->assertCount(2, $all);
    $this->assertSame($annotation1, $all['test1']);
    $this->assertSame($annotation2, $all['test2']);
    $this->assertArrayNotHasKey('.', $all);
});

test('allIncludingPrefixHandlers merges both', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);
    $prefixHandler = new TestAnnotation($processor);

    $registry->register('test', $annotation);
    $registry->registerPrefixHandler('.', $prefixHandler);

    $all = $registry->allIncludingPrefixHandlers();

    $this->assertCount(2, $all);
    $this->assertContains($annotation, $all);
    $this->assertContains($prefixHandler, $all);
});

test('setHighlighter propagates to all annotations', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation1 = new TestAnnotation($processor);
    $annotation2 = new TestAnnotation($processor);
    $prefixHandler = new TestAnnotation($processor);

    $registry->register('test1', $annotation1);
    $registry->register('test2', $annotation2);
    $registry->registerPrefixHandler('.', $prefixHandler);

    $highlighter = new Highlighter([]);
    $registry->setHighlighter($highlighter);

    $this->assertTrue($annotation1->highlighterWasSet);
    $this->assertTrue($annotation2->highlighterWasSet);
    $this->assertTrue($prefixHandler->highlighterWasSet);
});

test('setTorchlightOptions propagates to all annotations', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation1 = new TestAnnotation($processor);
    $annotation2 = new TestAnnotation($processor);
    $prefixHandler = new TestAnnotation($processor);

    $registry->register('test1', $annotation1);
    $registry->register('test2', $annotation2);
    $registry->registerPrefixHandler('.', $prefixHandler);

    $options = new Options;
    $registry->setTorchlightOptions($options);

    $this->assertTrue($annotation1->optionsWereSet);
    $this->assertTrue($annotation2->optionsWereSet);
    $this->assertTrue($prefixHandler->optionsWereSet);
});

test('setThemeResolver propagates to all annotations', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation1 = new TestAnnotation($processor);
    $annotation2 = new TestAnnotation($processor);
    $prefixHandler = new TestAnnotation($processor);

    $registry->register('test1', $annotation1);
    $registry->register('test2', $annotation2);
    $registry->registerPrefixHandler('.', $prefixHandler);

    $resolver = new ThemeStyleResolver([]);
    $registry->setThemeResolver($resolver);

    $this->assertTrue($annotation1->themeResolverWasSet);
    $this->assertTrue($annotation2->themeResolverWasSet);
    $this->assertTrue($prefixHandler->themeResolverWasSet);
});

test('setThemeResolver returns static for chaining', function (): void {
    $registry = new AnnotationRegistry;
    $resolver = new ThemeStyleResolver([]);

    $result = $registry->setThemeResolver($resolver);

    $this->assertSame($registry, $result);
});

test('register returns static for chaining', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $result = $registry->register('test', $annotation);

    $this->assertSame($registry, $result);
});

test('registerPrefixHandler returns static for chaining', function (): void {
    $registry = new AnnotationRegistry;
    $processor = new AnnotationEngine($registry);
    $annotation = new TestAnnotation($processor);

    $result = $registry->registerPrefixHandler('.', $annotation);

    $this->assertSame($registry, $result);
});

test('setHighlighter returns static for chaining', function (): void {
    $registry = new AnnotationRegistry;
    $highlighter = new Highlighter([]);

    $result = $registry->setHighlighter($highlighter);

    $this->assertSame($registry, $result);
});

test('setTorchlightOptions returns static for chaining', function (): void {
    $registry = new AnnotationRegistry;
    $options = new Options;

    $result = $registry->setTorchlightOptions($options);

    $this->assertSame($registry, $result);
});
