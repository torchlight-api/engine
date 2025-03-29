<?php

namespace Torchlight\Engine\Concerns;

trait LoadsGrammars
{
    protected array $extraGrammars = [
        'alpine' => __DIR__.'/../../resources/languages/alpine.tmLanguage.json',
        'curl' => __DIR__.'/../../resources/languages/curl.tmLanguage.json',
        'env' => __DIR__.'/../../resources/languages/env.tmLanguage.json',
        'files' => __DIR__.'/../../resources/languages/files.tmLanguage.json',
        'git-ignore' => __DIR__.'/../../resources/languages/ignore.tmLanguage.json',
        'mysql-explain' => __DIR__.'/../../resources/languages/mysql-explain.tmLanguage.json',
        'php-html' => __DIR__.'/../../resources/languages/php-html.tmLanguage.json',
        'shell' => __DIR__.'/../../resources/languages/shell.tmLanguage.json',
        'makefile' => __DIR__.'/../../resources/languages/make.tmLanguage.json',
        // TODO: Remove this custom grammar. This modified grammar just updates
        //       some custom HTML rules to help with syntax highlighting for
        //       some languages, such as Blade. It is just a workaround.
        //       https://github.com/phikiphp/phiki/issues/58
        'html' => __DIR__.'/../../resources/languages/html.tmLanguage.json',
    ];

    public static array $aliases = [
        'alpinejs' => 'alpine',
        'shellscript' => 'shell',
        'gitignore' => 'git-ignore',
        'pls' => 'plsql',
        'html-ruby-erb' => 'erb',
        'actionscript' => 'actionscript-3',
        'dockerfile' => 'docker',
        'make' => 'makefile',
    ];

    protected function loadGrammars(): static
    {
        foreach ($this->extraGrammars as $grammar => $file) {
            $this->environment->getGrammarRepository()->register($grammar, $file);
        }

        foreach (self::$aliases as $alias => $target) {
            $this->environment->getGrammarRepository()->alias($alias, $target);
        }

        return $this;
    }
}
