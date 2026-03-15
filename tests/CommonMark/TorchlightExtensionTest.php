<?php

use Torchlight\Engine\Options;
use Torchlight\Engine\Tests\Results\ResultParser;
use Torchlight\Engine\Tests\TorchlightTestCase;

uses(TorchlightTestCase::class);

test('it renders using the torchlight extension', function (): void {
    $markdown = $this->makeMarkdownConverter();
    $input = <<<'MD'

```php
class SomeCode
{

    public function theMethod(): void
    {
        return ''; // [tl! --]
        return; // [tl! ++]
    }

}
```

MD;

    $resultParser = new ResultParser;
    $result = $resultParser->parseResult($markdown->convert($input)->getContent());

    $this->assertTrue($result->line(6)->hasClass('line-remove'));
    $this->assertTrue($result->line(6)->hasClass('line-has-background'));
    $this->assertTrue($result->line(6)->hasClass('line'));

    $this->assertTrue($result->line(7)->hasClass('line-add'));
    $this->assertTrue($result->line(7)->hasClass('line-has-background'));
    $this->assertTrue($result->line(7)->hasClass('line'));
});

test('the torchlight extension respects global default options', function (): void {
    Options::setDefaultOptionsBuilder(fn () => new Options(lineNumbersEnabled: false));

    try {
        $markdown = $this->makeMarkdownConverter();
        $html = $markdown->convert(<<<'MD'
```php
echo 'hello';
```
MD)->getContent();

        expect($html)->not->toContain('class="line-number"');
    } finally {
        Options::setDefaultOptionsBuilder(null);
    }
});
