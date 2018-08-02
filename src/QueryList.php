<?php
namespace Rowbot\URL;

use Countable;
use Iterator;

use function count;
use function array_filter;
use function array_splice;
use function mb_convert_encoding;
use function strlen;
use function usort;

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
     * @var array<int, array<string, string>>
     */
    private $list;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->list = [];
        $this->cache = [];
        $this->cursor = 0;
    }

    /**
     * Appends a new name-value pair to the list.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function append($name, $value)
    {
        $this->list[] = ['name' => $name, 'value' => $value];
        $this->cache[$name] = true;
    }

    /**
     * Removes all name-value pairs with name $name from the list.
     *
     * @param string $name
     *
     * @return void
     */
    public function remove($name)
    {
        for ($i = count($this->list) - 1; $i >= 0; $i--) {
            if ($this->list[$i]['name'] === $name) {
                array_splice($this->list, $i, 1);
            }
        }

        unset($this->cache[$name]);
    }

    /**
     * Determines if a name-value pair with name $name exists in the collection.
     *
     * @param string $name
     *
     * @return bool
     */
    public function contains($name)
    {
        return isset($this->cache[$name]);
    }

    /**
     * Returns the first name-value pair in the list whose name is $name.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function first($name)
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
     * @param callable $callback
     *
     * @return array<int, array<string, string>>
     */
    public function filter(callable $callback)
    {
        return array_filter($this->list, $callback);
    }

    /**
     * Sets the value of the first name-value pair with $name to $value and
     * removes all other occurances that have name $name.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function set($name, $value)
    {
        $prevIndex = null;

        for ($i = count($this->list) - 1; $i >= 0; $i--) {
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
     *
     * @return int
     */
    public function count()
    {
        return count($this->list);
    }

    /**
     * Sorts the collection by code units and preserves the relative positioning
     * of name-value pairs.
     *
     * @return void
     */
    public function sort()
    {
        $array = [];
        $i = 0;

        foreach ($this->list as $pair) {
            $array[] = [$i++, $pair];
        }

        usort($array, function ($a, $b) {
            $len1 = strlen(mb_convert_encoding(
                $a[1]['name'],
                'UTF-16LE',
                'UTF-8'
            )) / 2;
            $len2 = strlen(mb_convert_encoding(
                $b[1]['name'],
                'UTF-16LE',
                'UTF-8'
            )) / 2;

            // Firstly, sort by number of code units.
            if ($len1 > $len2) {
                return -1;
            } elseif ($len1 < $len2) {
                return 1;
            }

            // If the number of code units is the same, fallback to sorting by
            // codepoints.
            if ($a[1]['name'] > $b[1]['name']) {
                return 1;
            } elseif ($a[1]['name'] < $b[1]['name']) {
                return -1;
            }

            // Finally, if all else is equal, sort by relative position.
            return $a[0] - $b[0];
        });

        foreach ($array as &$item) {
            $item = $item[1];
        }

        $this->list = $array;
    }

    /**
     * Clears the collection and cache.
     *
     * @return void
     */
    public function clear()
    {
        $this->list = [];
        $this->cache = [];
    }

    /**
     * Clears the collection and cache and then fills the collection with the
     * new name-value pairs in $list.
     *
     * @param array<int, array<string, string>> $list
     *
     * @return void
     */
    public function update(array $list)
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
     * @return array<int, array<string, string>>
     */
    public function all()
    {
        return $this->list;
    }

    /**
     * Returns the current name-value pair.
     *
     * @return array<string, string>
     */
    public function current()
    {
        return $this->list[$this->cursor];
    }

    /**
     * Returns the iterator key.
     *
     * @return int
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * Advances the iterator to the next position.
     *
     * @return void
     */
    public function next()
    {
        ++$this->cursor;
    }

    /**
     * Rewinds the iterator to the beginning.
     *
     * @return void
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * Returns whether the iterator is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->list[$this->cursor]);
    }
}
