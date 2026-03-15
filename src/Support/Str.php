<?php

namespace Torchlight\Engine\Support;

class Str
{
    /** @var non-empty-string */
    const CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function random(int $length = 16): string
    {
        $result = '';
        $maxIndex = strlen(static::CHARS) - 1;

        if ($maxIndex < 0) {
            return $result;
        }

        for ($i = 0; $i < $length; $i++) {
            $result .= static::CHARS[random_int(0, $maxIndex)];
        }

        return $result;
    }

    public static function substr(string|\Stringable|int|float|bool $string, int $start, ?int $length = null, ?string $encoding = 'UTF-8'): string
    {
        return mb_substr((string) $string, $start, $length, $encoding);
    }

    public static function beforeLast(string|\Stringable|int|float|bool $subject, string|\Stringable|int|float|bool $search): string
    {
        $search = (string) $search;

        if ($search === '') {
            return (string) $subject;
        }

        $subject = (string) $subject;
        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return static::substr($subject, 0, $pos);
    }

    public static function after(string|\Stringable|int|float|bool $subject, string|\Stringable|int|float|bool $search): string
    {
        $subject = (string) $subject;
        $search = (string) $search;

        if ($search === '') {
            return $subject;
        }

        return array_reverse(explode($search, $subject, 2))[0];
    }

    public static function afterLast(string|\Stringable|int|float|bool $subject, string|\Stringable|int|float|bool $search): string
    {
        $subject = (string) $subject;
        $search = (string) $search;

        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    /**
     * @return list<string>
     */
    public static function nlSplit(string $subject): array
    {
        return preg_split('/\r\n|\r|\n/', $subject) ?: [];
    }

    public static function substrReplace(
        string|\Stringable|int|float|bool $string,
        string|\Stringable|int|float|bool $replace,
        int $offset = 0,
        ?int $length = null,
    ): string {
        $string = (string) $string;
        $replace = (string) $replace;

        if ($length === null) {
            $length = strlen($string);
        }

        return substr_replace($string, $replace, $offset, $length);
    }
}
