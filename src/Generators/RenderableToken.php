<?php

namespace Torchlight\Engine\Generators;

use Phiki\Token\HighlightedToken;

class RenderableToken
{
    public function __construct(
        public HighlightedToken $highlighted,
        public TokenMetadata $metadata = new TokenMetadata,
    ) {}

    public static function from(HighlightedToken $token, ?string $rawContent = null): self
    {
        $metadata = new TokenMetadata;

        if ($rawContent !== null) {
            $metadata->setRawContent($rawContent);
        }

        return new self($token, $metadata);
    }

    public static function raw(HighlightedToken $token, string $content): self
    {
        $token->token->text = $content;

        return static::from($token, $content);
    }
}
