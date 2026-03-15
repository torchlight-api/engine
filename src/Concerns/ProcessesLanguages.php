<?php

namespace Torchlight\Engine\Concerns;

use Phiki\Token\Token;
use Torchlight\Engine\Annotations\Parser\AnnotationTokenParser;
use Torchlight\Engine\Exceptions\InvalidJsonException;

trait ProcessesLanguages
{
    /**
     * @param  array<int, array<int, Token>>  $tokens
     * @return array<int, array<int, Token>>
     */
    protected function processLanguage(array $tokens): array
    {
        $result = [];

        foreach ($tokens as $lineIndex => $lineTokens) {
            $lineNumber = $lineIndex + 1;

            // If annotations are disabled, pass through unchanged
            if (! $this->state->annotationsEnabled) {
                $result[] = $lineTokens;

                continue;
            }

            $processed = $this->processLanguageLine($lineTokens, $lineNumber);

            if ($processed !== null) {
                $result[] = $processed;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, Token>  $lineTokens  Tokens for this line
     * @param  int  $lineNumber  1-based line number
     * @return array<int, Token>|null Processed tokens or null to skip this line
     *
     * @throws InvalidJsonException
     */
    protected function processLanguageLine(array $lineTokens, int $lineNumber): ?array
    {
        $lineText = $this->getLineText($lineTokens);

        if ($lineNumber === 1 && str_contains((string) $lineText, $this->blockOptionsKeyword)) {
            $this->parseBlockOptions($lineText);

            return null;
        }

        $annotationCountBefore = count($this->state->parsedAnnotations);

        $result = $this->extractAnnotationsFromLine($lineTokens, $lineNumber);

        $normalFoundAnnotations = count($this->state->parsedAnnotations) > $annotationCountBefore;

        if ($result !== null && ! $normalFoundAnnotations) {
            $result = $this->handleUniversalAnnotation($result, $lineText, $lineNumber);
        }

        return $result;
    }

    /**
     * @param  array<int, Token>  $tokens
     */
    protected function getLineText(array $tokens): string
    {
        $text = '';
        foreach ($tokens as $token) {
            $text .= $token->text;
        }

        return $text;
    }

    /**
     * @param  array<int, Token>  $lineTokens
     * @return array<int, Token>|null
     */
    protected function extractAnnotationsFromLine(array $lineTokens, int $lineNumber): ?array
    {
        $result = [];
        $tokenCount = count($lineTokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $lineTokens[$i];
            $result[] = $token;

            if (! $this->isComment($token)) {
                continue;
            }

            $annotationResult = $this->findAnnotationInCommentSequence($lineTokens, $i, $tokenCount);

            if ($annotationResult === null) {
                continue;
            }

            [$foundToken, $forwardedTo, $extraTokens] = $annotationResult;

            foreach ($extraTokens as $extraToken) {
                $result[] = $extraToken;
            }

            $processResult = $this->processFoundAnnotation($foundToken, $result, $lineNumber, $tokenCount);

            if ($processResult === null) {
                return null;
            }

            $result = $processResult['tokens'];

            if ($processResult['addTrailing'] && $forwardedTo !== null && $forwardedTo + 1 < $tokenCount) {
                $nextToken = $lineTokens[$forwardedTo + 1];
                if ($this->isComment($nextToken)) {
                    $result[] = $nextToken;
                }
            }

            break;
        }

        return $result;
    }

    /**
     * @param  array<int, Token>  $lineTokens
     * @return array{0:Token, 1:int, 2:array<int, Token>}|null
     */
    protected function findAnnotationInCommentSequence(array $lineTokens, int $startIndex, int $tokenCount): ?array
    {
        $token = $lineTokens[$startIndex];

        if ($this->containsTorchlightAnnotation($token)) {
            return [$token, $startIndex, []];
        }

        $extraTokens = [];
        for ($j = $startIndex + 1; $j < $tokenCount; $j++) {
            $nextToken = $lineTokens[$j];

            if ($this->containsTorchlightAnnotation($nextToken)) {
                return [$nextToken, $j, $extraTokens];
            }

            $extraTokens[] = $nextToken;
        }

        return null;
    }

    /**
     * @param  array<int, Token>  $resultTokens
     * @return array<int, Token>|null
     */
    protected function handleUniversalAnnotation(array $resultTokens, string $lineText, int $lineNumber): ?array
    {
        $tlPos = mb_strpos($lineText, '[tl!');

        if ($tlPos === false) {
            return $resultTokens;
        }

        $beforeTl = mb_substr($lineText, 0, $tlPos);
        $slashSlashPos = mb_strrpos($beforeTl, '//');

        if ($slashSlashPos === false) {
            return $resultTokens;
        }

        // Ensure the // is preceded by whitespace or is at line start
        // Prevents matching :// in URLs like http://... and making a mess.
        if ($slashSlashPos > 0) {
            $charBefore = mb_substr($lineText, $slashSlashPos - 1, 1);

            if ($charBefore !== ' ' && $charBefore !== "\t") {
                return $resultTokens;
            }
        }

        $annotationSegment = mb_substr($lineText, $slashSlashPos);

        if (! preg_match('/^\/\/\s*\[tl!/', $annotationSegment)) {
            return $resultTokens;
        }

        if (! preg_match(AnnotationTokenParser::ANNOTATION_PATTERN, $annotationSegment)) {
            return $resultTokens;
        }

        $this->parseAnnotationsInText($annotationSegment, $lineNumber);

        $stripFrom = mb_strlen(rtrim(mb_substr($lineText, 0, $slashSlashPos)));
        $strippedTokens = $this->stripTextFromTokens($resultTokens, $stripFrom);

        if (empty($strippedTokens)) {
            return null;
        }

        $hasContent = false;

        foreach ($strippedTokens as $token) {
            if (trim((string) $token->text) !== '') {
                $hasContent = true;

                break;
            }
        }

        if (! $hasContent) {
            return null;
        }

        return $strippedTokens;
    }

    /**
     * @param  array<int, Token>  $tokens
     * @return array<int, Token>
     */
    protected function stripTextFromTokens(array $tokens, int $stripFromPos): array
    {
        $result = [];
        $currentPos = 0;

        foreach ($tokens as $token) {
            $tokenLen = mb_strlen((string) $token->text);
            $tokenEnd = $currentPos + $tokenLen;

            if ($tokenEnd <= $stripFromPos) {
                $result[] = $token;
            } elseif ($currentPos >= $stripFromPos) {
                continue;
            } else {
                $token->text = mb_substr((string) $token->text, 0, $stripFromPos - $currentPos);

                if (mb_strlen($token->text) > 0) {
                    $result[] = $token;
                }
            }

            $currentPos = $tokenEnd;
        }

        return $result;
    }

    /**
     * @param  array<int, Token>  $resultTokens
     * @return array{tokens: array<int, Token>, addTrailing: bool}|null
     */
    protected function processFoundAnnotation(Token $foundToken, array $resultTokens, int $lineNumber, int $tokenCount): ?array
    {
        if ($tokenCount === 1) {
            array_pop($resultTokens);
        }

        // Case 1: Annotation is the entire token
        if ($this->beginsWithAnnotation($foundToken)) {
            $this->parseAnnotationsInText($foundToken->text, $lineNumber);
            array_pop($resultTokens);

            if (count($resultTokens) === 0) {
                return null; // Skip line - it was only an annotation
            }

            return ['tokens' => $resultTokens, 'addTrailing' => false];
        }

        // Case 2: Annotation is embedded in a comment
        [$originalText, $cleanedToken] = $this->removeAnnotationFromToken($foundToken);
        $this->parseAnnotationsInText($originalText, $lineNumber);

        // Add the cleaned token if not already there
        if (empty($resultTokens) || $resultTokens[array_key_last($resultTokens)] !== $cleanedToken) {
            $resultTokens[] = $cleanedToken;
        }

        $lastToken = $resultTokens[array_key_last($resultTokens)];
        $cleanedText = $this->cleanCommentText($lastToken->text, $this->state->activeScopeName);

        // Remove empty comment tokens
        if (mb_strlen((string) $cleanedText) === 0) {
            array_pop($resultTokens);

            return ['tokens' => $resultTokens, 'addTrailing' => false];
        }

        // Remove whitespace-only single tokens
        if (count($resultTokens) === 1 && mb_strlen(trim((string) $lastToken->text)) === 0) {
            array_pop($resultTokens);
        }

        return ['tokens' => $resultTokens, 'addTrailing' => true];
    }
}
