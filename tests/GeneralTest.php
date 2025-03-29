<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);

use Torchlight\Engine\Options;
use Torchlight\Engine\Tests\Loaders\TestLoader;
use Torchlight\Engine\Tests\TestBlock;

test('general acceptance tests', function (TestBlock $test) {
    $language = $test->config['language'] ?? $test->request['options']['defaultLanguage'] ?? null;
    $options = array_merge($test->request['options'] ?? [], $test->config ?? []);
    $theme = $options['theme'] ?? 'nord';
    $options = Options::fromArray($options);

    $result = $this->toHtml($test->code, $language, $theme, $options);

    $test->save($result);

    $this->assertSame($test->expect, $result, $test->filePath);
})->with(fn () => TestLoader::load());
