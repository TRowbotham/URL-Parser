<?php
namespace phpjs\urls;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class QueryList implements Countable, IteratorAggregate
{
    private $list;
    private $cache;

    public function __construct(array $list = [])
    {
        $this->init($list);
    }

    private function init($list)
    {
        $this->list = $list;
        $this->cache = array_map(function ($index) {
            return true;
        }, array_flip(array_column($list, 0)));
    }

    public function append($name, $value)
    {
        $this->list[] = [$name, $value];
        $this->cache[$name] = true;
    }

    public function remove($name)
    {
        for ($i = count($this->list) - 1; $i >= 0; $i--) {
            if ($this->list[$i][0] === $name) {
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
            if ($name === $pair[0]) {
                return $pair[1];
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
            if ($this->list[$i][0] === $name) {
                if ($prevIndex !== null) {
                    array_splice($this->list, $prevIndex, 1);
                }

                $prevIndex = $i;
            }
        }

        $this->list[$prevIndex][1] = $value;
    }

    public function count()
    {
        return count($this->list);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->list);
    }

    public function sort()
    {
        usort($this->list, function ($pair1, $pair2) {
            $len1 = strlen(mb_convert_encoding($pair1[0], 'UTF-16LE')) / 2;
            $len2 = strlen(mb_convert_encoding($pair2[0], 'UTF-16LE')) / 2;

            if ($len1 > $len2) {
                return -1;
            } elseif ($len1 < $len2) {
                return 1;
            }

            return strcmp($pair1[0], $pair2[0]);
        });
    }

    public function clear()
    {
        $this->list = [];
        $this->cache = [];
    }

    public function update(array $list)
    {
        $this->init($list);
    }

    public function __toString()
    {
        return URLUtils::urlencodedSerializer(array_map(function ($pair) {
            return ['name' => $pair[0], 'value' => $pair[1]];
        }, $this->list));
    }
}
