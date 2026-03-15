<?php

namespace Torchlight\Engine\Theme;

use Phiki\Theme\ParsedTheme;
use Phiki\Theme\Theme as PhikiTheme;

class Theme
{
    /**
     * @param  array<string, string>  $overrides
     */
    public function __construct(
        public readonly string|PhikiTheme|ParsedTheme $base,
        public readonly array $overrides = [],
    ) {}

    public static function fromFile(string $path, ?string $name = null): self
    {
        $json = file_get_contents($path);
        $decoded = $json === false ? [] : json_decode($json, true);
        /** @var array<string, mixed> $data */
        $data = is_array($decoded) ? $decoded : [];

        if ($name !== null) {
            $data['name'] = $name;
        }

        return self::fromArray($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $parser = new Parser;
        $parsed = $parser->parse($data);

        return new self($parsed);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    public static function override(string|PhikiTheme|ParsedTheme $theme, array $overrides): self
    {
        return new self($theme, $overrides);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    public function withOverrides(array $overrides): self
    {
        return new self($this->base, array_merge($this->overrides, $overrides));
    }

    /**
     * @param  callable(string|PhikiTheme): ParsedTheme  $resolver
     */
    public function resolve(callable $resolver): ParsedTheme
    {
        $parsed = $this->base instanceof ParsedTheme
            ? $this->base
            : $resolver($this->base);

        if (empty($this->overrides)) {
            return $parsed;
        }

        return new ParsedTheme(
            $parsed->name,
            array_merge($parsed->colors, $this->overrides),
            $parsed->tokenColors
        );
    }
}
