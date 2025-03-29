<?php

namespace Torchlight\Engine\Concerns;

trait ProcessesLanguages
{
    protected function processLanguage(array $tokens): array
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

            $lineText = '';

            foreach ($line as $token) {
                $lineText .= $token->text;
            }

            for ($i = 0; $i < $tokenLen; $i++) {
                $token = $line[$i];

                $newLineTokens[] = $token;

                if ($currentLine === 1 && str_contains($lineText, 'torchlight! ')) {
                    $newLineTokens = [];
                    $skipLine = true;

                    $this->parseBlockOptions($lineText);
                    break;
                }

                if (! $this->isComment($token)) {
                    continue;
                }

                $foundToken = null;
                $forwardedTo = null;

                if ($this->containsTorchlightAnnotation($token)) {
                    $foundToken = $token;
                    $forwardedTo = $i;
                } else {
                    if ($i + 1 > $tokenLen) {
                        break;
                    }

                    for ($j = $i + 1; $j < $tokenLen; $j++) {
                        $commentToken = $line[$j];

                        if ($this->containsTorchlightAnnotation($commentToken)) {
                            $foundToken = $commentToken;
                            $forwardedTo = $j;
                            break;
                        }

                        $forwardedTo = $j;
                        $newLineTokens[] = $commentToken;
                    }
                }

                if ($foundToken != null) {
                    if ($tokenLen === 1) {
                        array_pop($newLineTokens);
                    }

                    if (! $this->beginsWithAnnotation($foundToken)) {
                        [$text, $newToken] = $this->removeAnnotationFromToken($foundToken);

                        $this->parseAnnotationsInText($text, $currentLine);

                        if (empty($newLineTokens) || $newLineTokens[array_key_last($newLineTokens)] != $newToken) {
                            $newLineTokens[] = $newToken;
                        }
                        $lastToken = $newLineTokens[array_key_last($newLineTokens)];

                        $cleanedText = $this->cleanCommentText($lastToken->text, $this->activeScopeName);

                        if (mb_strlen($cleanedText) === 0) {
                            array_pop($newLineTokens);
                        } else {
                            if ($forwardedTo && $forwardedTo + 1 < $tokenLen && $this->isComment($line[$forwardedTo + 1])) {
                                $newLineTokens[] = $line[$forwardedTo + 1];
                                $forwardedTo = null;
                            }
                        }

                        if (count($newLineTokens) === 1 && mb_strlen(trim($lastToken->text)) === 0) {
                            array_pop($newLineTokens);
                        }
                    } else {
                        $this->parseAnnotationsInText($foundToken->text, $currentLine);
                        array_pop($newLineTokens);

                        if (count($newLineTokens) === 0) {
                            $skipLine = true;
                        }
                    }

                    unset($foundToken);
                    break;
                }

                // Prevent duplicating contents if we've already added it.
                if ($forwardedTo) {
                    $i = $forwardedTo;
                }
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
