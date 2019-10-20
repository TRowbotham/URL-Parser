<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\RegexException;

use function explode;
use function mb_substr;
use function preg_last_error;
use function preg_match;
use function preg_replace;
use function sprintf;

use const PHP_INT_MAX;

class Utf8String extends AbstractString implements USVStringInterface
{
    public function append(string $string): USVStringInterface
    {
        return new self($this->string . $string);
    }

    public function matches(string $pattern, int $flags = 0, int $offset = 0): array
    {
        if (preg_match($pattern, $this->string, $matches, $flags, $offset) === false) {
            throw new RegexException(sprintf(
                'preg_match encountered an error with code %d trying to match "%s" against "%s".',
                preg_last_error(),
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
                'preg_replace encountered an error with code %d and pattern %s.',
                preg_last_error(),
                $pattern
            ));
        }

        return new self($result);
    }

    public function split(string $delimiter, int $limit = null): StringListInterface
    {
        $list = explode($delimiter, $this->string, $limit ?? PHP_INT_MAX);

        if ($list === false) {
            return new StringList();
        }

        $temp = [];

        foreach ($list as $string) {
            $temp[] = new self($string);
        }

        return new StringList($temp);
    }

    public function startsWithTwoAsciiHexDigits(): bool
    {
        if (!isset($this->string[1])) {
            return false;
        }

        return CodePoint::isAsciiHexDigit($this->string[0])
            && CodePoint::isAsciiHexDigit($this->string[1]);
    }

    public function startsWithWindowsDriveLetter(): bool
    {
        return preg_match('/^[A-Za-z][:|](?:$|[\/\\\?#])/u', $this->string) === 1;
    }

    public function substr(int $start, int $length = null): USVStringInterface
    {
        return new self(mb_substr($this->string, $start, $length, 'utf-8'));
    }
}
