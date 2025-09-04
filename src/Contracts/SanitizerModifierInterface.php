<?php
/**
 * Part of the "charcoal-dev/charsets" package.
 * @link https://github.com/charcoal-dev/charsets
 */

declare(strict_types=1);

namespace Charcoal\Charsets\Contracts;

use Charcoal\Contracts\Charsets\Charset;

/**
 * SanitizerModifierInterface
 */
interface SanitizerModifierInterface extends \UnitEnum
{
    public function apply(string $value, Charset $charset): string;
}