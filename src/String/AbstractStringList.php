<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\UndefinedIndexException;

use function array_pop;
use function count;

/**
 * @template T of object
 */
abstract class AbstractStringList
{
    /**
     * @var int
     */
    private $cursor;

    /**
     * @var array<int, T>
     */
    protected $list;

    /**
     * @param array<int, T> $list
     */
    public function __construct(array $list = [])
    {
        $this->cursor = 0;
        $this->list = $list;
    }

    public function count(): int
    {
        return count($this->list);
    }

    /**
     * @return T
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->list[$this->cursor];
    }

    /**
     * @return T
     */
    public function first()
    {
        if (!isset($this->list[0])) {
            throw new UndefinedIndexException();
        }

        return $this->list[0];
    }

    public function isEmpty(): bool
    {
        return $this->list === [];
    }

    public function key(): int
    {
        return $this->cursor;
    }

    /**
     * @return T
     */
    public function last()
    {
        $last = count($this->list) - 1;

        if ($last < 0) {
            throw new UndefinedIndexException();
        }

        return $this->list[$last];
    }

    public function next(): void
    {
        ++$this->cursor;
    }

    /**
     * @return T|null
     */
    public function pop()
    {
        return array_pop($this->list);
    }

    /**
     * @param T $string
     */
    public function push($string): void
    {
        $this->list[] = $string;
    }

    public function rewind(): void
    {
        $this->cursor = 0;
    }

    public function valid(): bool
    {
        return isset($this->list[$this->cursor]);
    }

    public function __clone()
    {
        $temp = [];

        foreach ($this->list as $string) {
            $temp[] = clone $string;
        }

        $this->list = $temp;
    }
}
