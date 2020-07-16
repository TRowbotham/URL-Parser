<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\RegexException;

use function preg_split;
use function sprintf;

use const PREG_SPLIT_NO_EMPTY;

class Utf8StringIterator implements StringIteratorInterface
{
    /**
     * @var array<int, string>
     */
    private $codePoints;

    /**
     * @var int
     */
    private $cursor;

    public function __construct(string $string)
    {
        // This shouldn't fail if the input string is valid utf-8.
        $codePoints = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);

        if ($codePoints === false) {
            throw new RegexException(sprintf(
                'preg_split encountered an error with message %s trying to split a string into '
                . 'code points.',
                RegexException::getNameFromLastCode()
            ));
        }

        $this->codePoints = $codePoints;
        $this->cursor = 0;
    }

    public function current(): string
    {
        return $this->codePoints[$this->cursor] ?? '';
    }

    public function key(): int
    {
        return $this->cursor;
    }

    public function next(): void
    {
        ++$this->cursor;
    }

    public function peek(int $count = 1): string
    {
        if ($count === 1) {
            return $this->codePoints[$this->cursor + 1] ?? '';
        }

        $output = '';
        $cursor = $this->cursor + 1;

        for ($i = 0; $i < $count; ++$i) {
            if (!isset($this->codePoints[$cursor])) {
                break;
            }

            $output .= $this->codePoints[$cursor];
            ++$cursor;
        }

        return $output;
    }

    public function prev(): void
    {
        --$this->cursor;
    }

    public function rewind(): void
    {
        $this->cursor = 0;
    }

    public function seek($position): void
    {
        $this->cursor += $position;
    }

    public function valid(): bool
    {
        return $this->cursor > -1 && isset($this->codePoints[$this->cursor]);
    }
}
