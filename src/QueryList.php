<?php
namespace Rowbot\URL;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function array_filter;
use function array_flip;
use function array_map;
use function array_splice;
use function in_array;
use function mb_convert_encoding;
use function strcmp;
use function strlen;
use function usort;

class QueryList implements Countable, IteratorAggregate
{
    private $list;
    private $cache;

    public function __construct(array $list = [])
    {
        $this->list = [];
        $this->cache = [];
        $this->appendAll($list);
    }

    public function append($name, $value)
    {
        $this->list[] = ['name' => $name, 'value' => $value];
        $this->cache[$name] = true;
    }

    public function appendAll($pairs)
    {
        foreach ($pairs as $pair) {
            $this->list[] = ['name' => $pair[0], 'value' => $pair[1]];
            $this->cache[$pair[0]] = true;
        }
    }

    public function remove($name)
    {
        for ($i = count($this->list) - 1; $i >= 0; $i--) {
            if ($this->list[$i]['name'] === $name) {
                array_splice($this->list, $i, 1);
            }
        }

        unset($this->cache[$name]);
    }

    public function contains($name)
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $exists = in_array($name, $this->list, true);
        $this->cache[$name] = $exists;

        return $exists;
    }

    public function first($name)
    {
        foreach ($this->list as $pair) {
            if ($pair['name'] === $name) {
                return $pair['value'];
            }
        }

        return null;
    }

    public function filter(callable $callback)
    {
        return array_filter($this->list, $callback);
    }

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

    public function count()
    {
        return count($this->list);
    }

    public function getIterator()
    {
        return new ArrayIterator(array_map(function ($pair) {
            return array_values($pair);
        }, $this->list));
    }

    public function sort()
    {
        usort($this->list, function ($pair1, $pair2) {
            $len1 = strlen(mb_convert_encoding($pair1['name'], 'UTF-16LE')) / 2;
            $len2 = strlen(mb_convert_encoding($pair2['name'], 'UTF-16LE')) / 2;

            if ($len1 > $len2) {
                return -1;
            } elseif ($len1 < $len2) {
                return 1;
            }

            return strcmp($pair1['name'], $pair2['name']);
        });
    }

    public function clear()
    {
        $this->list = [];
        $this->cache = [];
    }

    public function update(array $list)
    {
        $this->list = [];
        $this->cache = [];

        foreach ($list as $pair) {
            $this->list[] = $pair;
            $this->cache[$pair['name']] = true;
        }
    }

    public function __toString()
    {
        return URLUtils::urlencodedSerializer($this->list);
    }
}
