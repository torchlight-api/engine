<?php

namespace Torchlight\Engine\Generators\Concerns;

use Phiki\Theme\TokenSettings;
use Phiki\Token\HighlightedToken;
use Phiki\Token\Token;
use Torchlight\Engine\Generators\RenderableToken;
use Torchlight\Engine\Generators\TokenMetadata;

trait MergesHighlightedTokens
{
    /**
     * @param  array<int, RenderableToken[]>  $renderableLines
     * @return array<int, RenderableToken[]>
     */
    protected function mergeHighlightedTokens(array $renderableLines): array
    {
        foreach ($renderableLines as $lineIndex => $tokens) {
            $renderableLines[$lineIndex] = $this->mergeHighlightedLineTokens($tokens);
        }

        return $renderableLines;
    }

    /**
     * @param  RenderableToken[]  $tokens
     * @return RenderableToken[]
     */
    protected function mergeHighlightedLineTokens(array $tokens): array
    {
        if (empty($tokens)) {
            return $tokens;
        }

        $merged = [];
        $currentToken = null;

        foreach ($tokens as $token) {
            if ($currentToken === null) {
                $currentToken = $this->cloneRenderableToken($token);

                continue;
            }

            // Only merge if: same visual settings AND neither has custom metadata
            if ($this->tokensHaveSameVisualSettings($currentToken->highlighted, $token->highlighted)
                && ! $this->tokenHasCustomMetadata($currentToken)
                && ! $this->tokenHasCustomMetadata($token)) {
                // Merge: append text and update end position
                $currentToken->highlighted->token->text .= $token->highlighted->token->text;
                $currentToken->highlighted->token->end = $token->highlighted->token->end;
            } else {
                $merged[] = $currentToken;
                $currentToken = $this->cloneRenderableToken($token);
            }
        }
        $merged[] = $currentToken;

        return $merged;
    }

    protected function tokensHaveSameVisualSettings(HighlightedToken $a, HighlightedToken $b): bool
    {
        $settingsA = $a->settings;
        $settingsB = $b->settings;

        if (array_keys($settingsA) !== array_keys($settingsB)) {
            return false;
        }

        foreach ($settingsA as $themeId => $tokenSettingsA) {
            if (! isset($settingsB[$themeId])) {
                return false;
            }

            if (! $this->tokenSettingsAreEqual($tokenSettingsA, $settingsB[$themeId])) {
                return false;
            }
        }

        return true;
    }

    protected function tokenSettingsAreEqual(?TokenSettings $a, ?TokenSettings $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }

        if ($a === null || $b === null) {
            return false;
        }

        return $a->foreground === $b->foreground
            && $a->background === $b->background
            && $a->fontStyle === $b->fontStyle;
    }

    protected function cloneRenderableToken(RenderableToken $token): RenderableToken
    {
        $newToken = new Token(
            $token->highlighted->token->scopes,
            $token->highlighted->token->text,
            $token->highlighted->token->start,
            $token->highlighted->token->end
        );

        $newHighlighted = new HighlightedToken($newToken, $token->highlighted->settings);

        $newMetadata = new TokenMetadata(
            $token->metadata->classes,
            $token->metadata->attributes,
            $token->metadata->rawContent,
        );

        return new RenderableToken($newHighlighted, $newMetadata);
    }

    protected function tokenHasCustomMetadata(RenderableToken $token): bool
    {
        return $token->metadata->hasClasses()
            || $token->metadata->hasAttributes()
            || $token->metadata->isRaw();
    }
}
