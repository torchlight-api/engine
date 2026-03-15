<?php

namespace Torchlight\Engine\Concerns;

use Phiki\Token\Token;

trait ManagesCommentTokens
{
    protected string $commonChars = " \t-#/";

    /** @var array<string, string> */
    protected array $trailingCommentChars = [
        'source.coq' => '*)',
        'text.html.statamic' => '#}}',
        'text.html.jinja' => '#}',
    ];

    /** @var array<string, string> */
    protected array $leadingCommentChars = [
        'text.html.statamic' => '{{#',
        'text.html.jinja' => '{#',
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

    /**
     * @param  string  $scope  The TextMate scope name (e.g., 'source.mylang')
     * @param  string|null  $leadingChars  Characters to trim from the start of comments
     * @param  string|null  $trailingChars  Characters to trim from the end of comments
     */
    public function registerCommentPattern(
        string $scope,
        ?string $leadingChars = null,
        ?string $trailingChars = null
    ): static {
        if ($leadingChars !== null) {
            $this->leadingCommentChars[$scope] = $leadingChars;
        }

        if ($trailingChars !== null) {
            $this->trailingCommentChars[$scope] = $trailingChars;
        }

        return $this;
    }

    /**
     * @param  array<string, array{leading?: string, trailing?: string}>  $patterns
     */
    public function registerCommentPatterns(array $patterns): static
    {
        foreach ($patterns as $scope => $config) {
            $this->registerCommentPattern(
                $scope,
                $config['leading'] ?? null,
                $config['trailing'] ?? null
            );
        }

        return $this;
    }

    /**
     * @return array{leading: array<string, string>, trailing: array<string, string>}
     */
    public function getCommentPatterns(): array
    {
        return [
            'leading' => $this->leadingCommentChars,
            'trailing' => $this->trailingCommentChars,
        ];
    }

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
