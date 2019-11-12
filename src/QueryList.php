<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Countable;
use IntlBreakIterator;
use Iterator;

use function array_filter;
use function array_splice;
use function count;
use function usort;

/**
 * @implements \Iterator<int, array{name: string, value: string}>
 */
class QueryList implements Countable, Iterator
{
    /**
     * @var array<string, bool>
     */
    private $cache;

    /**
     * @var int
     */
    private $cursor;

    /**
     * @var array<int, array{name: string, value: string}>
     */
    private $list;

    public function __construct()
    {
        $this->list = [];
        $this->cache = [];
        $this->cursor = 0;
    }

    /**
     * Appends a new name-value pair to the list.
     */
    public function append(string $name, string $value): void
    {
        $this->list[] = ['name' => $name, 'value' => $value];
        $this->cache[$name] = true;
    }

    /**
     * Removes all name-value pairs with name $name from the list.
     */
    public function remove(string $name): void
    {
        for ($i = count($this->list) - 1; $i >= 0; --$i) {
            if ($this->list[$i]['name'] === $name) {
                array_splice($this->list, $i, 1);
            }
        }

        unset($this->cache[$name]);
    }

    /**
     * Determines if a name-value pair with name $name exists in the collection.
     */
    public function contains(string $name): bool
    {
        return isset($this->cache[$name]);
    }

    /**
     * Returns the first name-value pair in the list whose name is $name.
     */
    public function first(string $name): ?string
    {
        foreach ($this->list as $pair) {
            if ($pair['name'] === $name) {
                return $pair['value'];
            }
        }

        return null;
    }

    /**
     * Returns a filtered array based on the given callback.
     *
     *
     * @return array<int, array<string, string>>
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->list, $callback);
    }

    /**
     * Sets the value of the first name-value pair with $name to $value and
     * removes all other occurances that have name $name.
     */
    public function set(string $name, string $value): void
    {
        $prevIndex = null;

        for ($i = count($this->list) - 1; $i >= 0; --$i) {
            if ($this->list[$i]['name'] === $name) {
                if ($prevIndex !== null) {
                    array_splice($this->list, $prevIndex, 1);
                }

                $prevIndex = $i;
            }
        }

        $this->list[$prevIndex]['value'] = $value;
    }

    /**
     * Returns the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->list);
    }

    /**
     * Sorts the collection by code units and preserves the relative positioning
     * of name-value pairs.
     */
    public function sort(): void
    {
        $temp = [];
        $i = 0;

        // Add information about the relative position of each name-value pair.
        foreach ($this->list as $pair) {
            $temp[] = [$i++, $pair];
        }

        // Gasp! What is this black magic?
        $iter1 = IntlBreakIterator::createCodePointInstance();
        $iter2 = IntlBreakIterator::createCodePointInstance();

        // Sorting priority overview:
        //
        // Each string is compared character by character against each other.
        //
        // 1) If the two strings have different lengths, and the strings are
        //    equal up to the end of the shortest string, then the shorter of
        //    the two strings will be moved up in the array (ex. "aa" will come
        //    before "aaa").
        // 2) If the number of code units differ between the two characters,
        //    then the character with more code units will be moved up in the
        //    array (ex. "🌈" will come before "ﬃ").
        // 3) If the code points of the two characters are different, then the
        //    first string with a character with a lower code point will be
        //    moved up in the array (ex. "bba" will come before "bbb").
        usort($temp, static function (array $a, array $b) use ($iter1, $iter2): int {
            $iter1->setText($a[1]['name']);
            $iter2->setText($b[1]['name']);

            while (true) {
                $aIsValid = $iter1->next() !== IntlBreakIterator::DONE;
                $bIsValid = $iter2->next() !== IntlBreakIterator::DONE;

                if (!$aIsValid && !$bIsValid) {
                    // If we have reached this point then there are 2
                    // possibilities:
                    //
                    // 1) Both $a and $b are an empty string.
                    // 2) Both $a and $b have the same length and are considered
                    //    equal to each other.
                    //
                    // In either case, break out of the loop as we now need to
                    // compare the relative position of $a and $b to each other
                    // to settle the tie.
                    break;
                }

                if (!$aIsValid) {
                    // If we have reached this point then there are 2
                    // possibilities:
                    //
                    // 1) $a is an empty string and $b is not.
                    // 2) $a is a non-empty string, but its length is shorter
                    //    than $b.
                    //
                    // $a and $b are considered equal thus far, but $a is
                    // shorter so it gets to come before $b.
                    return -1;
                }

                if (!$bIsValid) {
                    // If we have reached this point then there are 2
                    // possibilities:
                    //
                    // 1) $b is an empty string and $a is not.
                    // 2) $b is a non-empty string, but its length is shorter
                    //    than $a.
                    //
                    // $a and $b are considered equal thus far, but $b is
                    // shorter so it gets to come before $a.
                    return 1;
                }

                $aCodePoint = $iter1->getLastCodePoint();
                $bCodePoint = $iter2->getLastCodePoint();

                // JavaScript likes to be fancy by using UTF-16 encoded strings.
                // In UTF-16, all code points in the Basic Multilingual Plane
                // (BMP) are represented by a single code unit. Code points
                // outside of the BMP (> 0xFFFF) are represented by 2 code
                // units.
                $aCodeUnits = $aCodePoint > 0xFFFF ? 2 : 1;
                $bCodeUnits = $bCodePoint > 0xFFFF ? 2 : 1;

                if ($aCodeUnits > $bCodeUnits) {
                    return -1;
                } elseif ($aCodeUnits < $bCodeUnits) {
                    return 1;
                } elseif ($aCodePoint > $bCodePoint) {
                    return 1;
                } elseif ($aCodePoint < $bCodePoint) {
                    return -1;
                }
            }

            // Finally, if all else is equal, sort by relative position.
            return $a[0] - $b[0];
        });

        // Remove the relative positioning information so that $temp only
        // contains the sorted name-value pairs.
        foreach ($temp as &$item) {
            $item = $item[1];
        }

        $this->list = $temp;
    }

    /**
     * Clears the collection and cache.
     */
    public function clear(): void
    {
        $this->list = [];
        $this->cache = [];
    }

    /**
     * Clears the collection and cache and then fills the collection with the
     * new name-value pairs in $list.
     *
     * @param array<int, array{name: string, value: string}> $list
     */
    public function update(array $list): void
    {
        $this->list = [];
        $this->cache = [];

        foreach ($list as $pair) {
            $this->list[] = $pair;
            $this->cache[$pair['name']] = true;
        }
    }

    /**
     * Returns the entire collection as an array.
     *
     * @return array<int, array{name: string, value: string}>
     */
    public function all(): array
    {
        return $this->list;
    }

    /**
     * Returns the current name-value pair.
     *
     * @return array{name: string, value: string}
     */
    public function current(): array
    {
        return $this->list[$this->cursor];
    }

    /**
     * Returns the iterator key.
     */
    public function key(): int
    {
        return $this->cursor;
    }

    /**
     * Advances the iterator to the next position.
     */
    public function next(): void
    {
        ++$this->cursor;
    }

    /**
     * Rewinds the iterator to the beginning.
     */
    public function rewind(): void
    {
        $this->cursor = 0;
    }

    /**
     * Returns whether the iterator is valid.
     */
    public function valid(): bool
    {
        return isset($this->list[$this->cursor]);
    }
}
