<?php

namespace Torchlight\Engine\Pipeline;

use Phiki\Grammar\Grammar;
use Phiki\Grammar\ParsedGrammar;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\TypeAliases;

/** @phpstan-import-type ThemeInput from TypeAliases */
class RenderState
{
    public string $activeScopeName = '';

    public Grammar|ParsedGrammar|null $resolvedGrammar = null;

    public string $resolvedLanguageName = '';

    /** @var ParsedAnnotation[] */
    public array $parsedAnnotations = [];

    public int $sourceLineOffset = 0;

    public bool $annotationsEnabled = true;

    public string $cleanedText = '';

    /** @var array<int|string, ThemeInput>|null */
    public ?array $overrideThemes = null;

    public string $languageVanityLabel = '';
}
