<?php

uses(\Torchlight\Engine\Tests\TorchlightTestCase::class);
use Torchlight\Engine\Tests\Results\ResultParser;

test('it renders using the torchlight extension', function () {
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
