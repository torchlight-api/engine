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
    protected ?BlockCache $cache = null;

    protected string $defaultGrammar = 'txt';

    public function __construct(
        private string|array|Theme $theme,
        private Engine $engine = new Engine,
        private bool $withGutter = false,
    ) {}

    protected array $renderCallbacks = [];

    public function setBlockCache(?BlockCache $cache): static
    {
        $this->cache = $cache;

        return $this;
    }

    public function setDefaultGrammar(string $grammar): static
    {
        $this->defaultGrammar = $grammar;

        return $this;
    }

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

        if ($this->cache && $this->cache->has($node)) {
            return $this->cache->get($node);
        }

        $code = rtrim($node->getLiteral(), "\n");
        $grammar = $this->detectGrammar($node, $code);

        $result = $this->engine->codeToHtml($code, $grammar, $this->theme, $this->withGutter, false);

        foreach ($this->renderCallbacks as $callback) {
            $result = $callback($result);
        }

        if ($this->cache) {
            $this->cache->set($node, $result);
        }

        return $result;
    }

    protected function detectGrammar(FencedCode $node, string $code): Grammar|string
    {
        if (! isset($node->getInfoWords()[0]) || $node->getInfoWords()[0] === '') {
            return $this->engine->detectGrammar($code) ?? $this->defaultGrammar;
        }

        return $node->getInfoWords()[0];
    }
}
