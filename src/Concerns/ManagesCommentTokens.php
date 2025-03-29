<?php

namespace Torchlight\Engine\Concerns;

use Phiki\Token\Token;

trait ManagesCommentTokens
{
    private string $commonChars = " \t-#/";

    private array $trailingCommentChars = [
        'source.coq' => '*)',
        'text.html.statamic' => '#}}',
    ];

    private array $leadingCommentChars = [
        'text.html.statamic' => '{{#',
        'source.coq' => '(*',
        'source.abap' => '"',
        'source.actionscript.3' => '/*',
        'source.php' => '/*#',
        'text.asciidoc' => '/',
        'text.beancount' => ';',
        'source.asm.x86_64' => ';',
        'source.clar' => ';',
        'source.cobol' => '*>',
    ];

    protected function isComment(Token $token): bool
    {
        $scopes = implode(' ', $token->scopes);

        if (isset($token->scopes[1]) && str_contains($scopes, 'comment')) {
            return true;
        }

        return false;
    }

    protected function cleanCommentText(string $text, string $scope): string
    {
        $trimChars = $this->commonChars;

        if (array_key_exists($scope, $this->leadingCommentChars)) {
            $trimChars .= $this->leadingCommentChars[$scope];
        }

        $cleanedText = ltrim($text, $trimChars);

        if (array_key_exists($scope, $this->trailingCommentChars)) {
            $trailingChars = $this->commonChars.$this->trailingCommentChars[$scope];

            $cleanedText = rtrim($cleanedText, $trailingChars);
        }

        // If we have an empty string after trimming, it
        // is likely that the Torchlight annotation
        // was the comment's only real content.
        if (mb_strlen(trim($cleanedText)) === 0) {
            return $cleanedText;
        }

        return $text;
    }
}
