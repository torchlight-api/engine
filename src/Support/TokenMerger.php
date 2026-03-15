<?php

namespace Torchlight\Engine\Support;

use Phiki\Token\Token;

class TokenMerger
{
    /**
     * @param  array<int, Token[]>  $lines
     * @return array<int, Token[]>
     */
    public static function merge(array $lines): array
    {
        foreach ($lines as $i => $tokens) {
            $merged = [];

            foreach ($tokens as $token) {
                if (! empty($merged)) {
                    $lastToken = end($merged);

                    if ($lastToken->scopes === $token->scopes) {
                        $lastToken->text .= $token->text;
                        $lastToken->end = $token->end;

                        continue;
                    }
                }

                $merged[] = $token;
            }

            $lines[$i] = $merged;
        }

        return $lines;
    }
}
