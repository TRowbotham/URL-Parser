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
    private $url;

    /**
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-urlsearchparams
     *
     * @param string[][]|object|string $init
     */
    public function __construct($init = '')
    {
        $this->list = new QueryList();
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
        $this->list = clone $this->list;

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
        return (string) $this->list;
    }

    public function toString()
    {
        return (string) $this->list;
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-new
     *
     * @internal
     *
     * @param  URLSearchParams|string $init The query string or another
     *                                      URLSearchParams object.
     *
     * @param  URLRecord              $url  The associated URLRecord object.
     *
     * @return URLSearchParams
     */
    public static function create($init, URLRecord $url)
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
                $this->list->append(
                    URLUtils::strval($pair[0]),
                    URLUtils::strval($pair[1])
                );
            }

            return;
        }

        if (is_object($init)) {
            foreach ($init as $name => $value) {
                $this->append($name, URLUtils::strval($value));
            }

            return;
        }

        if (is_string($init)) {
            $pairs = URLUtils::urlencodedStringParser($init);

            foreach ($pairs as $pair) {
                $this->list->append($pair['name'], $pair['value']);
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
        $this->list->append(URLUtils::strval($name), URLUtils::strval($value));
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
        $this->list->remove(URLUtils::strval($name));
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
        return $this->list->first(URLUtils::strval($name));
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

        return array_column($this->list->filter(function ($pair) use ($name) {
            return $pair[0] === $name;
        }), 1);
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
        return $this->list->contains(URLUtils::strval($name));
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

        if ($this->list->contains($name)) {
            $this->list->set($name, $value);
        } else {
            $this->list->append($name, $value);
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
        $this->list->sort();
        $this->update();
    }

    /**
     * Returns an iterator in the form of string[][].
     */
    public function getIterator()
    {
        return $this->list->getIterator();
    }

    /**
     * Clears the list of search params.
     *
     * @internal
     */
    public function clear()
    {
        $this->list->clear();
    }

    /**
     * Mutates the the list of query parameters without going through the
     * public API.
     *
     * @internal
     *
     * @param array $list A list of name-value pairs to be added to
     *     the list.
     */
    public function modify(array $list)
    {
        $this->list->update(array_map(function ($pair) {
            return [$pair['name'], $pair['value']];
        }, $list));
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
            $this->url->query = (string) $this->list;
        }
    }
}
