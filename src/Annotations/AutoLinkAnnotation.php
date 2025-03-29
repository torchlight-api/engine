<?php

namespace Torchlight\Engine\Annotations;

use Phiki\Token\HighlightedToken;
use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;

class AutoLinkAnnotation extends AbstractAnnotation
{
    const PATTERN_URL = '/\b((?:https?:\/\/|www\.)[^\s]+?)(?=[.,!?;:\]"\']?(?:\s|$))/i';

    public static string $name = 'autolink';

    public function process(ParsedAnnotation $annotation): void
    {
        $this->modifyRangeTokens(function ($tokens) {
            return $this->injectLinksIntoContent($tokens);
        });
    }

    protected function injectLinksIntoContent(array $line): array
    {
        /** @var HighlightedToken $token */
        foreach ($line as $token) {
            preg_match_all(static::PATTERN_URL, $token->token->text, $links);

            if (empty($links[0])) {
                continue;
            }

            $styles = implode('', $this->getTokenStyles($token));

            foreach ($links[0] as $href) {
                $link = '<a href="'.$href.'" target="_blank" rel="noopener" class="torchlight-link" style="'.$styles.'">'.htmlspecialchars($href).'</a>';

                $token->token->text = $this->safeReplace($href, $link, $token->token->text);
            }
        }

        return $line;
    }
}
