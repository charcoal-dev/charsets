<?php
/**
 * Part of the "charcoal-dev/charsets" package.
 * @link https://github.com/charcoal-dev/charsets
 */

declare(strict_types=1);

namespace Charcoal\Charsets\Support;

use Charcoal\Charsets\Contracts\UnicodeLanguageRangeInterface;

/**
 * The Utf8 class provides utility methods for working with UTF-8 strings.
 */
class Utf8Helper
{
    /**
     * @param string $unsafeString
     * @param bool $allowSpaces
     * @param bool $asciiCharset
     * @param UnicodeLanguageRangeInterface ...$languages
     * @return bool
     */
    public static function validate(
        string                        $unsafeString,
        bool                          $allowSpaces = true,
        bool                          $asciiCharset = true,
        UnicodeLanguageRangeInterface ...$languages
    ): bool
    {
        $regExp = static::generateRegExp($allowSpaces, $asciiCharset, ...$languages);
        if ($regExp === "") {
            return false;
        }
        return preg_match("/^[" . $regExp . "]+$/u", $unsafeString) === 1;
    }

    /**
     * @param string $input
     * @param bool $allowSpaces
     * @param bool $asciiCharset
     * @param UnicodeLanguageRangeInterface ...$languages
     * @return string
     */
    public static function filterOutExtras(
        string                        $input,
        bool                          $allowSpaces = true,
        bool                          $asciiCharset = true,
        UnicodeLanguageRangeInterface ...$languages
    ): string
    {
        $regExp = static::generateRegExp($allowSpaces, $asciiCharset, ...$languages);
        return $regExp ? (preg_replace("/[^" . $regExp . "]+/u", "", $input) ?? "") : "";
    }

    /**
     * @param bool $allowSpaces
     * @param bool $asciiCharset
     * @param UnicodeLanguageRangeInterface ...$languages
     * @return string
     */
    protected static function generateRegExp(
        bool                          $allowSpaces = true,
        bool                          $asciiCharset = true,
        UnicodeLanguageRangeInterface ...$languages
    ): string
    {
        return ($allowSpaces ? "\x20" : "") .
            ($asciiCharset ? "\x21-\x7E" : "") .
            static::getCompiledUnicodeRange(...$languages);
    }

    /**
     * @param UnicodeLanguageRangeInterface ...$languages
     * @return string
     */
    protected static function getCompiledUnicodeRange(
        UnicodeLanguageRangeInterface ...$languages
    ): string
    {
        $unicodeCharsets = "";
        foreach ($languages as $charset) {
            if ($charset instanceof UnicodeLanguageRangeInterface) {
                $unicodeCharsets .= implode("", $charset->getUnicodeRange());
            }
        }

        return $unicodeCharsets;
    }
}