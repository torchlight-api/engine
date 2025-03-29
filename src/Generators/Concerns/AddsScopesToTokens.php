<?php

namespace Torchlight\Engine\Generators\Concerns;

trait AddsScopesToTokens
{
    /**
     * @param  array[]  $tokens
     * @param  string[]  $scopes
     */
    protected function addScopesToTokens(int $line, array $tokens, array $scopes): array
    {
        if (! array_key_exists($line, $tokens)) {
            return $tokens;
        }

        for ($i = 0; $i < count($tokens[$line]); $i++) {
            foreach ($scopes as $scope) {
                $tokens[$line][$i]->scopes[] = $scope;
            }
        }

        return $tokens;
    }
}
