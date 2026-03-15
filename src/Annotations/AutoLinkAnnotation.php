<?php

namespace Torchlight\Engine\Annotations;

use Torchlight\Engine\Annotations\Parser\ParsedAnnotation;
use Torchlight\Engine\Generators\RenderableToken;

#[Annotation(name: 'autolink')]
class AutoLinkAnnotation extends AbstractAnnotation
{
    const PATTERN_URL = '/\b((?:https?:\/\/|www\.)[^\s]+?)(?=[.,!?;:\]"\']?(?:\s|$))/i';

    public function process(ParsedAnnotation $annotation): void
    {
        $this->modifyRangeTokens(
            function (array $tokens): array {
                /** @var array<int, RenderableToken> $tokens */
                return $this->injectLinksIntoContent($tokens);
            }
        );
    }

    /**
     * @param  array<int, RenderableToken>  $line
     * @return array<int, RenderableToken>
     */
    protected function injectLinksIntoContent(array $line): array
    {
        /** @var RenderableToken $token */
        foreach ($line as $token) {
            $tokenText = $token->highlighted->token->text;

            preg_match_all(self::PATTERN_URL, $tokenText, $links);

            if (empty($links[0])) {
                continue;
            }

            $styles = implode('', $this->themeResolver()->getTokenStyles($token->highlighted));

            foreach ($links[0] as $href) {
                $link = '<a href="'.$href.'" target="_blank" rel="noopener" class="torchlight-link" style="'.$styles.'">'.htmlspecialchars($href).'</a>';

                $tokenText = $this->safeReplace($href, $link, $tokenText);
            }

            $token->highlighted->token->text = $tokenText;
            $token->metadata->setRawContent($tokenText);
        }

        return $line;
    }
}
