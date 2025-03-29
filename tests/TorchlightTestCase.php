<?php

namespace Torchlight\Engine\Tests;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use Torchlight\Engine\Annotations\Parser\AnnotationTokenParser;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Annotations\Parser\ParseResult;
use Torchlight\Engine\Annotations\Ranges\ImpactedRange;
use Torchlight\Engine\Annotations\Ranges\RangeResolver;
use Torchlight\Engine\CommonMark\Extension;
use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;
use Torchlight\Engine\Tests\Results\Result;
use Torchlight\Engine\Tests\Results\ResultParser;

class TorchlightTestCase extends TestCase
{
    protected function makeEngine(): Engine
    {
        return new Engine;
    }

    protected function makeCommonMarkEnvironment(): Environment
    {
        return (new Environment)
            ->addExtension(new CommonMarkCoreExtension)
            ->addExtension(new Extension('github-light'));
    }

    protected function makeMarkdownConverter(): MarkdownConverter
    {
        return new MarkdownConverter($this->makeCommonMarkEnvironment());
    }

    protected function resolveNames(array $names = []): array
    {
        if (empty($names)) {
            $names = ['add', '++', 'remove', 'highlight', 'reindex', 'collapse'];
        }

        return $names;
    }

    protected function getImpactedRange(array $annotations, ParsedAnnotation $annotation, int $maxLines): ?ImpactedRange
    {
        return (new RangeResolver)
            ->setMaxLine($maxLines)
            ->setAnnotations($annotations)
            ->resolve($annotation);
    }

    protected function toHtml(string $code, string $grammar = 'php', string $theme = 'github-light', ?Options $options = null): string
    {
        $engine = $this->makeEngine();

        if ($options) {
            Options::setDefaultOptionsBuilder(fn () => $options);
        }

        $result = $engine->codeToHtml($code, $grammar, $theme, true, true);

        Options::setDefaultOptionsBuilder(null);

        return $result;
    }

    protected function toParsedResult(string $code, string $grammar = 'php', string $theme = 'github-light'): Result
    {
        $resultParser = new ResultParser;

        return $resultParser->parseResult($this->toHtml($code, $grammar, $theme));
    }

    protected function parseTokens(string $code, string $grammar = 'php'): array
    {
        return $this->makeEngine()->codeToTokens($code, $grammar);
    }

    protected function parseLineAnnotations(array $lines, array $names = []): array
    {
        $parser = new AnnotationTokenParser;
        $parser->setAnnotationNames($this->resolveNames($names));

        foreach ($lines as $line) {
            $parser->parseText($line[0], $line[1]);
        }

        return $parser->getAnnotations();
    }

    protected function parseAnnotations(string $text, array $names = [], int $lineNumber = 1): ParseResult
    {
        $parser = new AnnotationTokenParser;
        $parser->setAnnotationNames($this->resolveNames($names));

        return $parser->parseText($text, $lineNumber);
    }
}
