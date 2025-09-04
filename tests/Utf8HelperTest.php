<?php
/**
 * Part of the "charcoal-dev/charsets" package.
 * @link https://github.com/charcoal-dev/charsets
 */

declare(strict_types=1);

namespace Charcoal\Charsets\Tests;

use Charcoal\Charsets\Enums\Arabic;
use Charcoal\Charsets\Enums\Unicode;
use Charcoal\Charsets\Support\Utf8Helper;

/**
 * Class Utf8HelperTest
 * @package Charcoal\Charsets\Tests
 */
class Utf8HelperTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateAsciiDefaultsAllowPrintableAndSpace(): void
    {
        $this->assertTrue(Utf8Helper::validate("Hello World! ~`[](){}<>?/\\|'^$&*+-=_,.:;@#%"));
        $this->assertFalse(Utf8Helper::validate("Hello\nWorld"));
        $this->assertFalse(Utf8Helper::validate("Café"));
    }

    public function testValidateNoSpacesNoAsciiNoLanguagesReturnsFalse(): void
    {
        $this->assertFalse(Utf8Helper::validate("anything", allowSpaces: false, asciiCharset: false));
        $this->assertFalse(Utf8Helper::validate("", allowSpaces: false, asciiCharset: false));
    }

    public function testFilterOutExtrasNoSpacesNoAsciiNoLanguagesReturnsEmpty(): void
    {
        $this->assertSame("", Utf8Helper::filterOutExtras("abc", allowSpaces: false, asciiCharset: false));
        $this->assertSame("", Utf8Helper::filterOutExtras("", allowSpaces: false, asciiCharset: false));
    }

    public function testValidateWithCyrillicOnly(): void
    {
        $this->assertTrue(Utf8Helper::validate("Привет", false, false, Unicode::CYRILLIC));
        $this->assertFalse(Utf8Helper::validate("Hello", false, false, Unicode::CYRILLIC));
        $this->assertFalse(Utf8Helper::validate("Привет!", false, false, Unicode::CYRILLIC));
    }

    public function testValidateWithAsciiAndCyrillicTogether(): void
    {
        $this->assertTrue(Utf8Helper::validate("Тест-123", false, true, Unicode::CYRILLIC));
        $this->assertTrue(Utf8Helper::validate("Тест 123", true, true, Unicode::CYRILLIC));
        $this->assertFalse(Utf8Helper::validate("Γειά", false, true, Unicode::CYRILLIC));
    }

    public function testFilterOutExtrasKeepsOnlyCyrillicLettersWhenAsciiAndSpacesDisallowed(): void
    {
        $in = "Hello Привет ЖЗИ café 123!";
        $out = Utf8Helper::filterOutExtras($in, false, false, Unicode::CYRILLIC);
        $this->assertSame("ПриветЖЗИ", $out);
    }

    public function testFilterOutExtrasPreservesAsciiAndSpacesWhenAllowedAlongWithCyrillic(): void
    {
        $in = "ABC ЖЗИ 123 - _";
        $out = Utf8Helper::filterOutExtras($in, true, true, Unicode::CYRILLIC);
        $this->assertSame("ABC ЖЗИ 123 - _", $out);
    }

    public function testFilterOutExtrasRemovesTabsAndNewlinesEvenWhenSpacesAllowed(): void
    {
        $in = "A\tB\nC\r D";
        $out = Utf8Helper::filterOutExtras($in, allowSpaces: true, asciiCharset: true);
        $this->assertSame("ABC D", $out);
    }

    public function testValidateWithLatinSupplementOnly(): void
    {
        $this->assertTrue(Utf8Helper::validate("ÉÈÀÖÜ", false, false, Unicode::LATIN1_SUPPLEMENT));
        $this->assertFalse(Utf8Helper::validate("ABC", false, false, Unicode::LATIN1_SUPPLEMENT));
        $this->assertFalse(Utf8Helper::validate("É!", false, false, Unicode::LATIN1_SUPPLEMENT));
    }

    public function testFilterOutExtrasWithLatinSupplementAndAscii(): void
    {
        $in = "café Žlutý kůň! Привет";
        $out = Utf8Helper::filterOutExtras($in, true, true, Unicode::LATIN1_SUPPLEMENT);
        // Keeps ASCII + Latin-1 supplement; drops Cyrillic
        $this->assertSame("café lutý k! ", $out);
    }

    public function testFilterOutExtrasWithMultipleUnicodeRanges(): void
    {
        $in = "ABC ЖЗИ café ĄĆĘ";
        $languages = [Unicode::CYRILLIC,
            Unicode::LATIN1_SUPPLEMENT,
            Unicode::LATIN_EXTENDED_A];
        $out = Utf8Helper::filterOutExtras(
            $in,
            true,
            true,
            ...$languages
        );
        $this->assertSame("ABC ЖЗИ café ĄĆĘ", $out);
    }

    public function testValidateRejectsInvalidUtf8Sequences(): void
    {
        // Invalid UTF-8: lone continuation byte \x80 within string.
        $invalid = "A\x80B";
        $this->assertFalse(Utf8Helper::validate($invalid, allowSpaces: true, asciiCharset: true));
    }

    public function testFilterOutExtrasReturnsEmptyOnInvalidUtf8Sequences(): void
    {
        // preg_replace with /u returns null on invalid UTF-8; method converts it to "".
        $invalid = "A\x80B";
        $out = Utf8Helper::filterOutExtras($invalid, allowSpaces: true, asciiCharset: true);
        $this->assertSame("", $out);
    }

    public function testStressValidateLargeAsciiAllowed(): void
    {
        $asciiBlock = implode("", array_map("chr", range(32, 126))); // space + printable
        $in = str_repeat($asciiBlock, 1200);
        $this->assertTrue(Utf8Helper::validate($in, allowSpaces: true, asciiCharset: true));
    }

    public function testStressFilterOutExtrasLargeWithDisallowedInserted(): void
    {
        $asciiBlock = implode("", array_map("chr", range(32, 126)));
        $noise = "é" . "Ž" . "Ą" . "\xF0\x9F\x98\x80"; // accented letters and emoji
        $in = str_repeat($asciiBlock . $noise, 800);
        $out = Utf8Helper::filterOutExtras($in, allowSpaces: true, asciiCharset: true);
        $this->assertSame(strlen($asciiBlock) * 800, strlen($out));
        $this->assertStringNotContainsString("é", $out);
        $this->assertStringNotContainsString("Ž", $out);
        $this->assertStringNotContainsString("Ą", $out);
    }

    public function testFilterOutExtrasArabicModernWithAscii(): void
    {
        $in = "مرحبا - 123! سلام";
        $out = Utf8Helper::filterOutExtras($in, allowSpaces: true, asciiCharset: true, languages: Arabic::MODERN);
        // Keeps Arabic letters in the modern set + ASCII and spaces
        $this->assertSame("مرحبا - 123! سلام", $out);
    }

    public function testValidateArabicModernWithoutAsciiOrSpaces(): void
    {
        $this->assertTrue(Utf8Helper::validate("سلام", allowSpaces: false, asciiCharset: false, languages: Arabic::MODERN));
        $this->assertFalse(Utf8Helper::validate("سلام!", allowSpaces: false, asciiCharset: false, languages: Arabic::MODERN));
        $this->assertFalse(Utf8Helper::validate("Hello", allowSpaces: false, asciiCharset: false, languages: Arabic::MODERN));
    }
}