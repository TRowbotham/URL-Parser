<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\RegexException;
use Rowbot\URL\String\Exception\UConverterException;
use UConverter;

use function explode;
use function intval;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strlen;
use function substr;

use const PHP_INT_MAX;
use const U_ZERO_ERROR;

abstract class AbstractUSVString implements USVStringInterface
{
    /**
     * @var string
     */
    protected $string;

    public function __construct(string $string = '')
    {
        $this->string = $string;
    }

    public function append(string $string): USVStringInterface
    {
        $copy = clone $this;
        $copy->string .= $string;

        return $copy;
    }

    public function endsWith(string $string): bool
    {
        return substr($this->string, -strlen($string)) === $string;
    }

    public function getIterator(): StringIteratorInterface
    {
        return new Utf8StringIterator($this->string);
    }

    public function isEmpty(): bool
    {
        return $this->string === '';
    }

    public function length(): int
    {
        return mb_strlen($this->string, 'utf-8');
    }

    /**
     * @return array<int, string>
     */
    public function matches(string $pattern, int $flags = 0, int $offset = 0): array
    {
        if (preg_match($pattern, $this->string, $matches, $flags, $offset) === false) {
            throw new RegexException(sprintf(
                'preg_match encountered an error with message %s trying to match "%s" against "%s".',
                RegexException::getNameFromLastCode(),
                $this->string,
                $pattern
            ));
        }

        return $matches;
    }

    public function replaceRegex(
        string $pattern,
        string $replacement,
        int $limit = -1,
        int &$count = 0
    ): USVStringInterface {
        $result = preg_replace($pattern, $replacement, $this->string, $limit, $count);

        if ($result === null) {
            throw new RegexException(sprintf(
                'preg_replace encountered an error with message %s and pattern %s.',
                RegexException::getNameFromLastCode(),
                $pattern
            ));
        }

        $copy = clone $this;
        $copy->string = $result;

        return $copy;
    }

    public function split(string $delimiter, int $limit = null): StringListInterface
    {
        $list = explode($delimiter, $this->string, $limit ?? PHP_INT_MAX);

        if ($list === false) {
            return new StringList();
        }

        $temp = [];

        foreach ($list as $string) {
            $copy = clone $this;
            $copy->string = $string;
            $temp[] = $copy;
        }

        return new StringList($temp);
    }

    public function startsWith(string $string): bool
    {
        return substr($this->string, 0, strlen($string)) === $string;
    }

    public function startsWithTwoAsciiHexDigits(): bool
    {
        if (!isset($this->string[1])) {
            return false;
        }

        return CodePoint::isAsciiHexDigit($this->string[0])
            && CodePoint::isAsciiHexDigit($this->string[1]);
    }

    /**
     * @see https://url.spec.whatwg.org/#start-with-a-windows-drive-letter
     */
    public function startsWithWindowsDriveLetter(): bool
    {
        return preg_match('/^[A-Za-z][:|](?:$|[\/\\\?#])/u', $this->string) === 1;
    }

    public function substr(int $start, int $length = null): USVStringInterface
    {
        $copy = clone $this;
        $copy->string = mb_substr($this->string, $start, $length, 'utf-8');

        return $copy;
    }

    public function toInt(int $base = 10): int
    {
        return intval($this->string, $base);
    }

    public static function transcode(
        string $string,
        string $toEncoding,
        string $fromEncoding
    ): string {
        $converter = new UConverter($toEncoding, $fromEncoding);

        if ($converter->getErrorCode() !== U_ZERO_ERROR) {
            throw new UConverterException(sprintf(
                'Attempting to create the UConverter object with encodings "%s" and "%s" failed with message "%s".',
                $toEncoding,
                $fromEncoding,
                $converter->getErrorMessage()
            ), $converter->getErrorCode());
        }

        $transcodedString = $converter->convert($string);

        if ($converter->getErrorCode() !== U_ZERO_ERROR) {
            throw new UConverterException(sprintf(
                'Attempting to transcode from "%s" to "%s" failed with message "%s".',
                $fromEncoding,
                $toEncoding,
                $converter->getErrorMessage()
            ), $converter->getErrorCode());
        }

        return $transcodedString;
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
