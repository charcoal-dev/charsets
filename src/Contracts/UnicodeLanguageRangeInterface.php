<?php
/**
 * Part of the "charcoal-dev/charsets" package.
 * @link https://github.com/charcoal-dev/charsets
 */

declare(strict_types=1);

namespace Charcoal\Charsets\Contracts;

use Charcoal\Charsets\Enums\Locale;

/**
 * UnicodeLanguageRangeInterface
 */
interface UnicodeLanguageRangeInterface
{
    /**
     * @return string[]
     */
    public function getUnicodeRange(): array;

    /**
     * @return Locale[]
     */
    public function getLocales(): array;

    /**
     * @param Locale|null $locale
     * @return self|null
     */
    public static function fromLocale(?Locale $locale): ?self;
}