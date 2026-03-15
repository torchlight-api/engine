<?php

namespace Torchlight\Engine\CommonMark;

use Closure;
use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Phiki\Grammar\Grammar;
use Phiki\Theme\ParsedTheme;
use Phiki\Theme\Theme;
use Stringable;
use Torchlight\Engine\Engine;
use Torchlight\Engine\Theme\Theme as TorchlightTheme;

class CodeBlockRenderer implements NodeRendererInterface
{
    protected ?BlockCache $cache = null;

    protected string $defaultGrammar = 'txt';

    /**
     * @param  string|Theme|TorchlightTheme|ParsedTheme|array<int|string, string|Theme|TorchlightTheme|ParsedTheme>  $theme
     */
    public function __construct(
        private readonly string|array|Theme|TorchlightTheme|ParsedTheme $theme,
        private readonly Engine $engine = new Engine,
    ) {}

    /** @var list<Closure(string): string> */
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

    /** @param Closure(string): string $callback */
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

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string|Stringable|null
    {
        if (! $node instanceof FencedCode) {
            throw new InvalidArgumentException('Block must be instance of '.FencedCode::class);
        }

        if ($this->cache && $this->cache->has($node)) {
            return $this->cache->get($node);
        }

        $code = rtrim($node->getLiteral(), "\n");
        $grammar = $this->detectGrammar($node);

        $result = $this->engine->codeToHtml($code, $grammar, $this->theme);

        foreach ($this->renderCallbacks as $callback) {
            $result = $callback($result);
        }

        if ($this->cache) {
            $this->cache->set($node, $result);
        }

        return $result;
    }

    protected function detectGrammar(FencedCode $node): Grammar|string
    {
        if (! isset($node->getInfoWords()[0]) || $node->getInfoWords()[0] === '') {
            return $this->defaultGrammar;
        }

        return $node->getInfoWords()[0];
    }
}
