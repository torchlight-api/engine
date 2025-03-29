<?php

namespace Torchlight\Engine\Concerns;

use Torchlight\Engine\Exceptions\InvalidJsonException;

trait ProcessesTextJson
{
    /**
     * @throws InvalidJsonException
     */
    protected function processTextOrJson(array $tokens): array
    {
        $newTokens = [];
        $currentLine = 1;

        /** @var Token[] $line */
        foreach ($tokens as $line) {
            if (! $this->annotationsEnabled) {
                $newTokens[] = $line;

                $currentLine++;

                continue;
            }

            $newLineTokens = [];
            $tokenLen = count($line);
            $skipLine = false;

            for ($i = 0; $i < $tokenLen; $i++) {
                $token = $line[$i];

                if ($currentLine === 1) {
                    if ($this->isPlainText() && str_contains($token->text, 'torchlight! ')) {
                        $this->parseBlockOptions($token->text);

                        $skipLine = true;
                        break;
                    } elseif ($this->isComment($token) && $i + 1 < $tokenLen && str_contains($line[$i + 1]->text, 'torchlight! ')) {
                        $this->parseBlockOptions($line[$i + 1]->text);

                        $skipLine = true;
                        break;
                    }
                }

                if (! $this->containsTorchlightAnnotation($token)) {
                    $newLineTokens[] = $token;

                    continue;
                }

                [$annotationText, $newToken] = $this->removeAnnotationFromToken($token);

                if ($this->isPlainText()) {
                    $trimmedText = rtrim($newToken->text);

                    // Clean up danging comments
                    if (str_ends_with($trimmedText, '//')) {
                        $newToken->text = rtrim(mb_substr($trimmedText, 0, -2))."\n";
                    }
                }

                if ($this->isJson() && $i > 0) {
                    /** @var Token $lastToken */
                    $lastToken = $newLineTokens[count($newLineTokens) - 1];

                    if (trim($lastToken->text) === '//') {
                        array_pop($newLineTokens);
                    }
                }

                $this->parseAnnotationsInText($annotationText, $currentLine);

                $newLineTokens[] = $newToken;
            }

            $currentLine++;

            if ($skipLine) {
                continue;
            }

            $newTokens[] = $newLineTokens;
        }

        return $newTokens;
    }
}
