<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);
use Torchlight\Engine\Tests\Loaders\LeadingCommentTextLoader;

test('it preserves leading and trailing comment text', function (string $language, string $commentStyle, string $code) {
    $result = $this->toParsedResult($code, $language);
    $this->assertTrue($result->line(1)->isHighlighted());
    $this->assertTrue($result->line(1)->hasBackground());

    $this->assertStringContainsString('TheLeadingCommentText', $result->line(1)->text);
    $this->assertStringContainsString('TheTrailingCommentText', $result->line(1)->text);

    for ($i = 2; $i <= $result->lineCount(); $i++) {
        $this->assertFalse($result->line($i)->isHighlighted());
        $this->assertFalse($result->line($i)->hasBackground());
    }
})->with(function () {
    return LeadingCommentTextLoader::load();
});
