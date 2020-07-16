<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\String\Exception\RegexException;
use Rowbot\URL\String\Exception\UConverterException;
use Rowbot\URL\String\Exception\UndefinedIndexException;
use Rowbot\URL\String\StringList;
use Rowbot\URL\String\Utf8String;

use function is_int;

class StringsTest extends TestCase
{
    public function testTranscodeUnknownEncoding(): void
    {
        $this->expectWarning();
        Utf8String::transcode('stuff', 'gallifreyan', 'utf-8');
    }

    public function startsWithTwoAsciiHexDigitsProvider(): array
    {
        return [
            ['ab', true],
            ['a', false],
            ['99', true],
            ['a3', true],
            ['3a', true],
            ['a4x', true],
            ['AB', true],
            ['3F', true],
            ['gab', false],
            ['', false],
        ];
    }

    /**
     * @dataProvider startsWithTwoAsciiHexDigitsProvider
     */
    public function testStartsWithTwoAsciiHexDigits(string $input, bool $expected): void
    {
        $s = new Utf8String($input);
        self::assertSame($expected, $s->startsWithTwoAsciiHexDigits());
    }

    public function startsWithWindowsDriveLetterProvider(): array
    {
        return [
            ['c:', true],
            ['c:/', true],
            ['c:a', false],
            ['4:', false],
            ['az:', false],
            ['a|', true],
            ['a:|', false],
            ['', false],
            ['c:\\', true],
            ['c:?', true],
            ['c:#', true],
            ['c:/f', true],
        ];
    }

    /**
     * @dataProvider startsWithWindowsDriveLetterProvider
     */
    public function testStartsWithWindowsDriveLetter(string $input, bool $expected): void
    {
        $s = new Utf8String($input);
        self::assertSame($expected, $s->startsWithWindowsDriveLetter());
    }

    public function testMatchesThrowsWhenOffsetExceedsLength(): void
    {
        $this->expectException(RegexException::class);
        $s = new Utf8String('');
        $s->matches('/[A-Z]/', 0, 1);
    }

    public function testMatchesThrowsOnInvalidUtf8Text(): void
    {
        $this->expectException(RegexException::class);
        $s = new Utf8String("\xC3\x7F");
        $s->matches('/[A-Z]/u');
    }

    public function testReplaceRegexThrowsOnInvalidUtf8Text(): void
    {
        $this->expectException(RegexException::class);
        $s = new Utf8String("\xC3\x7F");
        $s->replaceRegex('/[A-Z]/u', 'foo');
    }

    /**
     * @requires PHP < 8
     */
    public function testSplitReturnsEmptyListWithEmptyDelimiter(): void
    {
        $s = new Utf8String('');
        // PHP warns when passing an empty delimiter to \explode(), the underlying function, so we
        // must silence the warning to test return value of ::split(). In PHP 8+ the warning becomes
        // an error and explode no longer returns false.
        $list = @$s->split('');
        $this->assertTrue($list->isEmpty());
    }

    /**
     * @requires PHP >= 8
     */
    public function testSplitErrorsWithEmptyDelimiter(): void
    {
        $this->expectError();
        $s = new Utf8String('');
        $s->split('');
    }

    public function testStringListFirstThrowsWithEmptyList(): void
    {
        $this->expectException(UndefinedIndexException::class);
        $list = new StringList();
        $list->first();
    }

    public function testStringListLastThrowsWithEmptyList(): void
    {
        $this->expectException(UndefinedIndexException::class);
        $list = new StringList();
        $list->last();
    }

    public function testStringListKeyReturnsInteger(): void
    {
        $s = new Utf8String('a=b=c=d');

        foreach ($s->split('=') as $key => $string) {
            // ::assertIsInt() isn't available on PHPUnit 7
            $this->assertTrue(is_int($key));
        }
    }
}
