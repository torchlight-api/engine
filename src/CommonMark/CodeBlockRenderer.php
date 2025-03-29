<?php

namespace Torchlight\Engine\CommonMark;

use Closure;
use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Phiki\Grammar\Grammar;
use Phiki\Theme\Theme;
use Torchlight\Engine\Engine;

class CodeBlockRenderer implements NodeRendererInterface
{
    public function __construct(
        private string|array|Theme $theme,
        private Engine $engine = new Engine,
        private bool $withGutter = false,
    ) {}

    protected array $renderCallbacks = [];

    public function addRenderCallback(Closure $callback): static
    {
        $this->renderCallbacks[] = $callback;

        return $this;
    }

    public function clearRenderCallbacks(): static
    {
        $this->renderCallbacks = [];

        return $this;
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (! $node instanceof FencedCode) {
            throw new InvalidArgumentException('Block must be instance of '.FencedCode::class);
        }

        $code = rtrim($node->getLiteral(), "\n");
        $grammar = $this->detectGrammar($node, $code);

        $result = $this->engine->codeToHtml($code, $grammar, $this->theme, $this->withGutter, false);

        foreach ($this->renderCallbacks as $callback) {
            $result = $callback($result);
        }

        return $result;
    }

    protected function detectGrammar(FencedCode $node, string $code): Grammar|string
    {
        if (! isset($node->getInfoWords()[0]) || $node->getInfoWords()[0] === '') {
            return $this->engine->detectGrammar($code) ?? 'txt';
        }

        return $node->getInfoWords()[0];
    }
}
