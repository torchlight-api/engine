<?php

namespace Torchlight\Engine\Generators\TokenTransformers;

use Phiki\Theme\TokenSettings;
use Phiki\Token\HighlightedToken;
use Torchlight\Engine\Contracts\TokenTransformer;
use Torchlight\Engine\Generators\RenderableToken;
use Torchlight\Engine\Generators\RenderContext;

class FileTreeTransformer implements TokenTransformer
{
    protected string $fileClassPrefix = 'tl-files';

    protected string $connectorClassPrefix = 'tl-connect';

    protected string $grammarName = 'files';

    /** @var list<string> */
    protected array $commentScopes = [
        'source.files',
        'comment.line.number-sign.yaml',
        'punctuation.definition.comment.yaml',
    ];

    public function __construct(protected TreeConnectorGrid $connectorGrid = new TreeConnectorGrid) {}

    public function setFileClassPrefix(string $prefix): static
    {
        $this->fileClassPrefix = $prefix;

        return $this;
    }

    public function getFileClassPrefix(): string
    {
        return $this->fileClassPrefix;
    }

    public function setConnectorClassPrefix(string $prefix): static
    {
        $this->connectorClassPrefix = $prefix;

        return $this;
    }

    public function getConnectorClassPrefix(): string
    {
        return $this->connectorClassPrefix;
    }

    public function setGrammarName(string $name): static
    {
        $this->grammarName = $name;

        return $this;
    }

    public function getGrammarName(): string
    {
        return $this->grammarName;
    }

    /** @param list<string> $scopes */
    public function setCommentScopes(array $scopes): static
    {
        $this->commentScopes = $scopes;

        return $this;
    }

    /** @return list<string> */
    public function getCommentScopes(): array
    {
        return $this->commentScopes;
    }

    public function supports(string $grammarName): bool
    {
        return $grammarName === $this->grammarName;
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $tokens
     * @return array<int, array<int, RenderableToken>>
     */
    public function transform(RenderContext $context, array $tokens): array
    {
        $lineInfo = $this->extractLineMetadata($tokens);
        $lineInfo = $this->normalizeDepths($lineInfo);

        $grid = $this->connectorGrid->build($lineInfo);

        $commentSettings = $context->getScopeSettings($this->commentScopes);

        $style = $context->options->fileStyle ?? 'ascii';

        if ($style === 'ascii') {
            return $this->renderAsciiConnectors($tokens, $grid, $lineInfo, $commentSettings);
        }

        return $this->renderHtmlConnectors($tokens, $grid, $lineInfo, $commentSettings);
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $lines
     * @return array<int, array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}>
     */
    protected function extractLineMetadata(array $lines): array
    {
        $info = [];

        foreach ($lines as $lineIndex => $tokens) {
            $info[$lineIndex] = [
                'depth' => 0,
                'isCommentOnly' => false,
                'isDirectory' => false,
                'content' => '',
            ];

            if (empty($tokens)) {
                continue;
            }

            /** @var RenderableToken $token */
            foreach ($tokens as $token) {
                $tokenText = $token->highlighted->token->text;

                // Whitespace token determines depth
                if (trim($tokenText) == '') {
                    $info[$lineIndex]['depth'] = mb_strlen($tokenText);

                    continue;
                }

                // Comment lines start with #
                if (str_starts_with($tokenText, '#')) {
                    $info[$lineIndex]['isCommentOnly'] = true;
                    break;
                }

                // Directory entries end with /
                $isDirectory = str_ends_with($tokenText, '/');
                $info[$lineIndex]['isDirectory'] = $isDirectory;
                $info[$lineIndex]['content'] = $tokenText;

                // Set metadata on the token
                $attributes = [];

                if (! $isDirectory) {
                    $attributes['tl-file-extension'] = htmlspecialchars(
                        pathinfo($token->highlighted->token->text, PATHINFO_EXTENSION)
                    );
                }

                $token->metadata->classes = [
                    $isDirectory
                        ? "{$this->fileClassPrefix}-folder"
                        : "{$this->fileClassPrefix}-file",
                    "{$this->fileClassPrefix}-name",
                ];
                $token->metadata->attributes = $attributes;

                break;
            }
        }

        return $info;
    }

    /**
     * @param  array<int, array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}>  $info
     * @return array<int, array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}>
     */
    protected function normalizeDepths(array $info): array
    {
        $allDepths = array_map(fn ($i) => $i['depth'], $info);
        $uniqueDepths = array_unique($allDepths);
        sort($uniqueDepths);

        $levels = [];
        foreach ($uniqueDepths as $i => $level) {
            $levels[$level] = $i;
        }

        return array_map(function ($line) use ($levels) {
            $levelIndex = $levels[$line['depth']];
            $line['depth'] = $levelIndex * 3;

            return $line;
        }, $info);
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $lines
     * @param  array<int, list<int|string>>  $grid
     * @param  array<int, array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}>  $lineInfo
     * @param  array<string, TokenSettings>  $commentSettings
     * @return array<int, array<int, RenderableToken>>
     */
    protected function renderAsciiConnectors(array $lines, array $grid, array $lineInfo, array $commentSettings): array
    {
        foreach ($lines as $lineIndex => $tokens) {
            if (empty($tokens) || empty($grid[$lineIndex])) {
                continue;
            }

            /** @var RenderableToken $token */
            foreach ($tokens as $tokenIndex => $token) {
                if (trim($token->highlighted->token->text) !== '') {
                    continue;
                }

                // Convert bitmask grid row to ASCII characters
                $asciiContent = $this->connectorGrid->rowToAscii($grid[$lineIndex]);

                // Update the token text
                $token->highlighted->token->text = $asciiContent;

                // Create new HighlightedToken with comment settings wrapped in RenderableToken
                $newHighlighted = new HighlightedToken(
                    $token->highlighted->token,
                    $commentSettings,
                );

                $lines[$lineIndex][$tokenIndex] = new RenderableToken(
                    $newHighlighted,
                    $token->metadata,
                );

                break;
            }
        }

        return $lines;
    }

    /**
     * @param  array<int, array<int, RenderableToken>>  $lines
     * @param  array<int, list<int|string>>  $grid
     * @param  array<int, array{depth:int, isCommentOnly:bool, isDirectory:bool, content:string}>  $lineInfo
     * @param  array<string, TokenSettings>  $commentSettings
     * @return array<int, array<int, RenderableToken>>
     */
    protected function renderHtmlConnectors(array $lines, array $grid, array $lineInfo, array $commentSettings): array
    {
        foreach ($lines as $lineIndex => $tokens) {
            if (empty($tokens) || empty($grid[$lineIndex])) {
                continue;
            }

            /** @var RenderableToken $token */
            foreach ($tokens as $tokenIndex => $token) {
                if (trim($token->highlighted->token->text) !== '') {
                    continue;
                }

                // Convert bitmask grid row to HTML
                $rawContent = $this->connectorGrid->rowToHtml(
                    $grid[$lineIndex],
                    $this->connectorClassPrefix
                );

                // Update the token text
                $token->highlighted->token->text = $rawContent;

                // Create new HighlightedToken with comment settings
                $newHighlighted = new HighlightedToken(
                    $token->highlighted->token,
                    $commentSettings,
                );

                // Replace with new RenderableToken with raw content metadata
                $lines[$lineIndex][$tokenIndex] = new RenderableToken(
                    $newHighlighted,
                    $token->metadata->setRawContent($rawContent),
                );

                break;
            }
        }

        return $lines;
    }
}
