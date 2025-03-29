<?php

namespace Torchlight\Engine\Generators\Gutters;

use Torchlight\Engine\Annotations\Processor;
use Torchlight\Engine\Generators\Concerns\InteractsWithHtmlRenderer;
use Torchlight\Engine\Generators\HtmlGenerator;
use Torchlight\Engine\Options;

abstract class AbstractGutter
{
    use InteractsWithHtmlRenderer;

    protected Options $options;

    protected ?HtmlGenerator $htmlGenerator = null;

    public function __construct(
        protected Processor $engine,
    ) {
        $this->options = Options::default();
    }

    public function setHtmlGenerator(HtmlGenerator $generator): static
    {
        $this->htmlGenerator = $generator;

        return $this;
    }

    public function setTorchlightOptions(Options $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function reset(): void {}

    abstract public function renderLine(int $relativeLine, int $index, array $tokens): string;

    public function shouldRender(): bool
    {
        return true;
    }
}
