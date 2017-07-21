<?php
namespace phpjs\urls;

use ArrayIterator;
use IteratorAggregate;
use phpjs\urls\exception\TypeError;
use Traversable;

/**
 * An object containing a list of all URL query parameters.  This allows you to
 * manipulate a URL's query string in a granular manner.
 *
 * @see https://url.spec.whatwg.org/#urlsearchparams
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
 */
class URLSearchParams implements IteratorAggregate
{
    private $list;
    private $params;
    private $position;
    private $sequenceId;
    private $url;

    /**
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-urlsearchparams
     *
     * @param string[][]|object|string $init
     */
    public function __construct($init = '')
    {
        $this->list = [];
        $this->params = [];
        $this->sequenceId = 0;
        $this->position = 0;
        $this->url = null;

        // If $init is given, is a string, and starts with "?", remove the
        // first code point from $init.
        if (func_num_args() > 0) {
            if (is_string($init) && mb_substr($init, 0, 1) === '?') {
                $init = mb_substr($init, 1);
            }

            $this->init($init);
        }
    }

    public function __clone()
    {
        // Null out the url incase someone tries cloning the object returned by
        // the URL::searchParams attribute.
        $this->url = null;
    }

    /**
     * Returns all name-value pairs stringified in the correct order.
     *
     * @return string The query string.
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        $list = [];

        foreach ($this->list as $sequenceId => $pair) {
            $list[] = [
                'name'  => $pair[0],
                'value' => $pair[1]
            ];
        }

        return URLUtils::urlencodedSerializer($list);
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-new
     *
     * @internal
     *
     * @param  URLSearchParams|string $init The query string or another
     *                                      URLSearchParams object.
     *
     * @param  URLRecord|null         $url  The associated URLRecord object.
     *
     * @return URLSearchParams
     */
    public static function create($init, URLRecord $url = null)
    {
        $query = new self();
        $query->url = $url;
        $query->init($init);

        return $query;
    }

    /**
     * @internal
     *
     * @param  URLSearchParams|string $init
     */
    private function init($init)
    {
        if (is_array($init) || $init instanceof Traversable) {
            foreach ($init as $pair) {
                if (count($pair) != 2) {
                    throw new TypeError();
                    return;
                }
            }

            foreach ($init as $pair) {
                $name = URLUtils::strval($pair[0]);
                $value = URLUtils::strval($pair[1]);

                $this->list[$this->sequenceId] = [$name, $value];
                $this->params[$name][$this->sequenceId++] = $value;
            }

            return;
        }

        if (is_object($init)) {
            foreach ($init as $name => $value) {
                $this->list[$this->sequenceId] = [$name, $value];
                $this->params[$name][$this->sequenceId++] = URLUtils::strval(
                    $value
                );
            }

            return;
        }

        if (is_string($init)) {
            $pairs = URLUtils::urlencodedStringParser($init);

            foreach ($pairs as $pair) {
                $this->list[$this->sequenceId] = [$pair['name'], $pair['value']];
                $this->params[$pair['name']][$this->sequenceId++] = $pair[
                    'value'
                ];
            }
        }
    }

    /**
     * Appends a new name-value pair to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-append
     *
     * @param string $name  The name of the key in the pair.
     *
     * @param string $value The value assigned to the key.
     */
    public function append($name, $value)
    {
        $name = URLUtils::strval($name);
        $value = URLUtils::strval($value);

        if (!is_string($name) || !is_string($value)) {
            return;
        }

        $this->list[$this->sequenceId] = [$name, $value];
        $this->params[$name][$this->sequenceId++] = $value;
        $this->update();
    }

    /**
     * Deletes all occurances of pairs with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-delete
     *
     * @param  string $name The name of the key to delete.
     */
    public function delete($name)
    {
        $name = URLUtils::strval($name);

        if (!is_string($name) || !isset($this->params[$name])) {
            return;
        }

        foreach ($this->params[$name] as $key => $value) {
            unset($this->list[$key]);
        }

        unset($this->params[$name]);
        $this->update();
    }

    /**
     * Get the value of the first name-value pair with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-get
     *
     * @param string $name The name of the key whose value you want to retrive.
     *
     * @return string The value of the specified key.
     */
    public function get($name)
    {
        $name = URLUtils::strval($name);

        return is_string($name) && isset($this->params[$name]) ?
            reset($this->params[$name]) : null;
    }

    /**
     * Gets all name-value pairs that has the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-getall
     *
     * @param string $name The name of the key whose values you want to
     *     retrieve.
     *
     * @return string[] An array containing all the values of the specified key.
     */
    public function getAll($name)
    {
        $name = URLUtils::strval($name);

        return is_string($name) && isset($this->params[$name]) ?
            array_values($this->params[$name]) : [];
    }

    /**
     * Indicates whether or not a query string contains any keys with the
     * specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-has
     *
     * @param boolean $name The key name you want to test if it exists.
     *
     * @return boolean         Returns true if the key exits, otherwise false.
     */
    public function has($name)
    {
        $name = URLUtils::strval($name);

        return is_string($name) && isset($this->params[$name]);
    }

    /**
     * Sets the value of the specified key name.  If multiple pairs exist with
     * the same key name it will set the value for the first occurance of the
     * key in the query string and all other occurances will be removed from the
     * query string.  If the key does not already exist in the query string, it
     * will be added to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-set
     *
     * @param string $name  The name of the key you want to modify the value of.
     *
     * @param string $value The value you want to associate with the key name.
     */
    public function set($name, $value)
    {
        $name = URLUtils::strval($name);
        $value = URLUtils::strval($value);

        if (!is_string($name) || !is_string($value)) {
            return;
        }

        if (isset($this->params[$name])) {
            for ($i = count($this->params[$name]) - 1; $i > 0; $i--) {
                end($this->params[$name]);
                unset($this->list[key($this->params[$name])]);
                array_pop($this->params[$name]);
            }

            reset($this->params[$name]);
            $id = key($this->params[$name]);
            $this->list[$id][1] = $value;
            $this->params[$name][$id] = $value;
        } else {
            // Append the value
            $this->list[$this->sequenceId] = [$name, $value];
            $this->params[$name][$this->sequenceId++] = $value;
        }

        $this->update();
    }

    /**
     * Sorts the list of search params by their names by comparing their code
     * unit values, preserving the relative order between pairs with the same
     * name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-sort
     */
    public function sort()
    {
        $list = [];
        $params = [];
        $sequenceIdx = 0;

        foreach ($this->list as $sequenceId => $pair) {
            $name = $pair[0];
            $value = $pair[1];

            if ($sequenceIdx == 0) {
                $list[] = [$name, $value];
                $params[$name] = [];
                $sequenceIdx++;
                continue;
            }

            if (isset($params[$name])) {
                $i = $sequenceIdx;

                while ($i) {
                    if ($list[$i - 1][0] === $name) {
                        break;
                    }

                    $i--;
                }
            } else {
                $i = $sequenceIdx - 1;
                $params[$name] = [];
                $len1 = strlen(mb_convert_encoding(
                    $name,
                    'UTF-16LE',
                    'UTF-8'
                )) / 2;
                $len2 = strlen(mb_convert_encoding(
                    $list[$i][0],
                    'UTF-16LE',
                    'UTF-8'
                )) / 2;

                while ($i && $len1 > $len2) {
                    $i--;
                    $len2 = strlen(mb_convert_encoding(
                        $list[$i][0],
                        'UTF-16LE',
                        'UTF-8'
                    )) / 2;
                }
            }

            array_splice($list, $i, 0, [[$name, $value]]);
            $sequenceIdx++;
        }

        foreach ($list as $sequenceId => $pair) {
            $params[$pair[0]][$sequenceId] = $pair[1];
        }

        $this->list = $list;
        $this->params = $params;
        $this->sequenceId = $sequenceIdx;
        $this->update();
    }

    /**
     * Returns an iterator in the form of string[][].
     */
    public function getIterator()
    {
        return new ArrayIterator(array_values($this->list));
    }

    /**
     * Clears the list of search params.
     *
     * @internal
     */
    public function clear()
    {
        $this->list = [];
        $this->params = [];
        $this->sequenceId = 0;
    }

    /**
     * Mutates the the list of query parameters without going through the
     * public API.
     *
     * @internal
     *
     * @param array $list A list of name -> value pairs to be added to
     *     the list.
     */
    public function _mutateList(array $list)
    {
        $this->list = [];
        $this->params = [];
        $this->sequenceId = 0;

        foreach ($list as $pair) {
            $this->list[$this->sequenceId] = [
                $pair['name'],
                $pair['value']
            ];
            $this->params[$pair['name']][$this->sequenceId++] = $pair[
                'value'
            ];
        }
    }

    /**
     * Set's the associated URL object's query to the serialization of
     * URLSearchParams.
     *
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-update
     *
     * @internal
     */
    protected function update()
    {
        if ($this->url) {
            $this->url->query = $this->toString();
        }
    }
}
