<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);
use Torchlight\Engine\Tests\Loaders\AnnotationCleanupLoader;
use Torchlight\Engine\Tests\Results\ResultParser;

test('annotations do not leak between renders', function (string $language, string $commentStyle, string $annotatedCode, string $code) {
    $phiki = $this->makeEngine();
    $resultParser = new ResultParser;

    $annotatedResult = $resultParser->parseResult($phiki->codeToHtml($annotatedCode, $language, 'github-light'));
    $plainResult = $resultParser->parseResult($phiki->codeToHtml($code, $language, 'github-light'));

    $this->assertTrue($annotatedResult->line(1)->isHighlighted());
    $this->assertTrue($annotatedResult->line(1)->hasBackground());

    for ($i = 2; $i <= $annotatedResult->lineCount(); $i++) {
        $this->assertFalse($annotatedResult->line($i)->isHighlighted());
        $this->assertFalse($annotatedResult->line($i)->hasBackground());
    }

    for ($i = 1; $i <= $plainResult->lineCount(); $i++) {
        $this->assertFalse($plainResult->line($i)->isHighlighted());
        $this->assertFalse($plainResult->line($i)->hasBackground());
    }
})->with(function () {
    return AnnotationCleanupLoader::load();
});
