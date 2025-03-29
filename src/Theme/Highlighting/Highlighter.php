<?php

namespace Torchlight\Engine\Theme\Highlighting;

use Phiki\Highlighter as BaseHighlighter;
use Phiki\Token\HighlightedToken;

// TODO: Investigate and work to make this custom Highlighter class unnecessary.
readonly class Highlighter extends BaseHighlighter
{
    private SettingsResolver $resolver;

    public function __construct(
        public array $themes
    ) {
        $this->resolver = new SettingsResolver;
    }

    public function highlight(array $tokens): array
    {
        $highlightedTokens = [];

        foreach ($tokens as $i => $line) {
            foreach ($line as $token) {
                if (mb_strlen(trim($token->text)) === 0) {
                    $highlightedTokens[$i][] = new HighlightedToken($token, []);

                    continue;
                }

                $scopes = array_reverse($token->scopes);
                $settings = [];

                foreach ($this->themes as $id => $theme) {
                    foreach ($scopes as $scope) {
                        $resolved = $this->resolver->resolve($theme, $scope);
                        // Commented out and left here to make it easier to compare with Phiki results.
                        // $resolved = $theme->resolve($scope);

                        if ($resolved !== null) {
                            $settings[$id] = $resolved;

                            break;
                        }

                    }
                }

                $highlightedTokens[$i][] = new HighlightedToken($token, $settings);
            }
        }

        return $highlightedTokens;
    }
}
