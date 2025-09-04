<?php
/**
 * Part of the "charcoal-dev/charsets" package.
 * @link https://github.com/charcoal-dev/charsets
 */

declare(strict_types=1);

namespace Charcoal\Charsets\Tests;

use Charcoal\Charsets\Support\AsciiHelper;

/**
 * Class AsciiHelperTest
 * @package Charcoal\Charsets\Tests
 */
class AsciiHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testIsPrintableOnlyWithPrintableAscii(): void
    {
        $s = "Hello, World! 123 ~!@#[](){}<>:?/\\|`'^$&*+-=_.,";
        $this->assertTrue(AsciiHelper::isPrintableOnly($s));
    }

    public function testIsPrintableOnlyWithControlAndExtendedBytes(): void
    {
        $s = "Printable\x07 Bell" . "\n" . "AndHigh\xC3\xA9"; // includes BEL, LF, and non-ASCII UTF-8 bytes
        $this->assertFalse(AsciiHelper::isPrintableOnly($s));
    }

    public function testInCharsetAcceptsAllAsciiIncludingControls(): void
    {
        $s = "\x00\t\n\r ABC\x7F"; // includes NUL, TAB, LF, CR, DEL and printable
        $this->assertTrue(AsciiHelper::inCharset($s));
    }

    public function testInCharsetRejectsNonAscii(): void
    {
        $s = "ASCII and é ñ 😊"; // contains multi-byte
        $this->assertFalse(AsciiHelper::inCharset($s));
    }

    public function testSanitizeUseRegExDefaultRemovesControlsHighAndKeepsPrintable(): void
    {
        $in = "A\tB\nC\rD\x00E\x7FFé😊Z";
        $out = AsciiHelper::sanitizeUseRegEx($in);
        $this->assertSame("ABCDEFZ", $out);
    }

    public function testSanitizeUseRegExKeepsTabWhenAllowed(): void
    {
        $in = "A\tB C";
        $out = AsciiHelper::sanitizeUseRegEx($in, tabChar: true);
        $this->assertSame("A\tB C", $out);
    }

    public function testSanitizeUseRegExKeepsLineBreaksWhenAllowed(): void
    {
        $in = "A\nB\rC";
        $out = AsciiHelper::sanitizeUseRegEx($in, lineBreaks: true);
        $this->assertSame("A\nB\rC", $out);
    }

    public function testSanitizeUseRegExKeepsNullByteWhenAllowed(): void
    {
        $in = "A\0B C";
        $out = AsciiHelper::sanitizeUseRegEx($in, nullByte: true);
        $this->assertSame("A\0B C", $out);
    }

    public function testSanitizeUseFilterStripsLowAndHigh(): void
    {
        $in = "\x00A\tB\nC\rD\x7FÉ😊Z";
        $out = AsciiHelper::sanitizeUseFilter($in);
        $this->assertSame("ABCDZ", $out);
    }

    public function testSanitizeByByteRemovesNonPrintableByDefault(): void
    {
        $in = "A\tB\nC\rD\x00E\x7FZ";
        $out = AsciiHelper::sanitizeByByte($in);
        $this->assertSame("ABCDEZ", $out);
    }

    public function testSanitizeByByteAllowsSpecificNonPrintable(): void
    {
        $in = "A\tB\nC\rD\x00E\x7FZ";
        $out = AsciiHelper::sanitizeByByte($in, allowChars: "\t\n\r\0");
        $this->assertSame("A\tB\nC\rD\0EZ", $out); // \x7F is still removed
    }

    public function testSanitizeByByteDeniesSpecificPrintable(): void
    {
        $in = "Safe-String_123";
        $out = AsciiHelper::sanitizeByByte($in, denyChars: "-_");
        $this->assertSame("SafeString123", $out);
    }

    public function testSanitizeByByteEmptyInputReturnsEmpty(): void
    {
        $this->assertSame("", AsciiHelper::sanitizeByByte(""));
    }

    public function testSanitizeByByteAllowsExplicitBytesAboveAsciiRange(): void
    {
        // bytes above 126 are removed unless explicitly allowed
        $in = "A" . "\x80" . "\xFF" . "Z";
        $out = AsciiHelper::sanitizeByByte($in, allowChars: "\x80\xFF");
        $this->assertSame("A" . "\x80" . "\xFF" . "Z", $out);
    }

    public function testSanitizeByByteDenyTakesPrecedenceForPrintableOnly(): void
    {
        // For printable characters, denyChars removes them.
        $in = "ABC";
        $out = AsciiHelper::sanitizeByByte($in, allowChars: "AB", denyChars: "B");
        $this->assertSame("AC", $out);
    }

    public function testSanitizeUseRegExStressLargePrintable(): void
    {
        $chunk = implode("", array_map("chr", range(32, 126)));
        $in = str_repeat($chunk, 2000); // ~190k chars
        $out = AsciiHelper::sanitizeUseRegEx($in);
        $this->assertSame(strlen($in), strlen($out));
        $this->assertSame(substr($in, 0, 256), substr($out, 0, 256));
    }

    public function testSanitizeUseRegExStressMixedData(): void
    {
        $printable = implode("", array_map("chr", range(32, 126)));
        $noise = "\x00\x01\x02\x7F\x80\xFF" . "é😊";
        $in = str_repeat($printable . $noise, 2000);
        $out = AsciiHelper::sanitizeUseRegEx($in);
        $this->assertStringNotContainsString("\x00", $out);
        $this->assertStringNotContainsString("\x7F", $out);
        $this->assertStringNotContainsString("é", $out);
        $this->assertStringNotContainsString("😊", $out);
        $this->assertSame(95 * 2000, strlen($out)); // 95 printable ASCII chars per block
    }

    public function testSanitizeUseFilterStressMixedData(): void
    {
        $printable = implode("", array_map("chr", range(32, 126)));
        $noise = "\x00\x01\x02\x7F\x80\xFF" . "é😊";
        $in = str_repeat($printable . $noise, 1500);
        $out = AsciiHelper::sanitizeUseFilter($in);
        $this->assertSame(95 * 1500, strlen($out));
    }

    public function testSanitizeByByteStressWithAllowAndDeny(): void
    {
        $printable = implode("", array_map("chr", range(32, 126)));
        $deny = "-_[]{}";
        $allow = "\n\r\t\0\x7F";
        $noise = "\x00\x01\x02\x7F\x80\xFF" . "é😊" . "\n\r\t";
        $in = str_repeat($printable . $deny . $noise, 800);
        $out = AsciiHelper::sanitizeByByte($in, allowChars: $allow, denyChars: $deny);

        // All printable minus denied; plus allowed non-printables (\n \r \t and \x00, \x7F); high bytes removed.
        $expectedPrintableCount = 95 - strlen($deny); // 89
        $allowedControlsPerBlock = 3 + 1 + 1;         // 5
        $expectedPerBlock = $expectedPrintableCount + $allowedControlsPerBlock; // 94
        $this->assertSame($expectedPerBlock * 800, strlen($out));
    }
}