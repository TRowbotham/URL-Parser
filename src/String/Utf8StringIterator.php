<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use IntlBreakIterator;

use function iterator_to_array;

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
        $iter = IntlBreakIterator::createCodePointInstance();
        $iter->setText($string);
        $this->codePoints = iterator_to_array($iter->getPartsIterator());
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
            return '';
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
