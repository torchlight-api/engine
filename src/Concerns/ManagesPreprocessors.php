<?php

namespace Torchlight\Engine\Concerns;

use Closure;
use Phiki\Grammar\Grammar;
use Torchlight\Engine\Contracts\Preprocessor;
use Torchlight\Engine\Preprocessors\PreprocessorArgs;

trait ManagesPreprocessors
{
    protected array $preprocessors = [];

    protected array $languageSpecificPreprocessors = [];

    public function registerPreprocessorForLanguage(string $languageName, Closure|Preprocessor $preprocessor): static
    {
        if (! array_key_exists($languageName, $this->languageSpecificPreprocessors)) {
            $this->languageSpecificPreprocessors[$languageName] = [];
        }

        $this->languageSpecificPreprocessors[$languageName][] = $preprocessor;

        return $this;
    }

    public function registerPreprocessor(Closure|Preprocessor $preprocessor, ?string $languageName = null): static
    {
        if ($languageName) {
            return $this->registerPreprocessorForLanguage($languageName, $preprocessor);
        }

        $this->preprocessors[] = $preprocessor;

        return $this;
    }

    protected function runPreprocessors(array $tokens, string $originalCode, string|Grammar $grammar, ?string $languageName, array $preprocessors): array
    {
        $args = new PreprocessorArgs(
            $tokens,
            $originalCode,
            $grammar,
            $languageName
        );

        foreach ($preprocessors as $preprocessor) {
            if ($preprocessor instanceof Preprocessor) {
                $tokens = $preprocessor->process($args, $this);
            } elseif (is_callable($preprocessor)) {
                $tokens = $preprocessor($args, $this);
            }
        }

        return $tokens;
    }

    protected function preprocess(array $tokens, string $originalCode, string|Grammar $grammar, ?string $languageName): array
    {
        if (count($this->preprocessors) > 0) {
            $tokens = $this->runPreprocessors($tokens, $originalCode, $grammar, $languageName, $this->preprocessors);
        }

        if (! $languageName) {
            return $tokens;
        }

        if (isset($this->languageSpecificPreprocessors[$languageName])) {
            $tokens = $this->runPreprocessors($tokens, $originalCode, $grammar, $languageName, $this->languageSpecificPreprocessors[$languageName]);
        }

        return $tokens;
    }
}
