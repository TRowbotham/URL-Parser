<?php
namespace phpjs\urls;

use ArrayIterator;
use IteratorAggregate;
use phpjs\exceptions\TypeError;
use phpjs\Utils;
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
    private $mList;
    private $mParams;
    private $mPosition;
    private $mSequenceId;
    private $mUrl;

    /**
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-urlsearchparams
     *
     * @param string[][]|object|string $init
     */
    public function __construct($init = '')
    {
        $this->mList = [];
        $this->mParams = [];
        $this->mSequenceId = 0;
        $this->mPosition = 0;
        $this->mUrl = null;

        // If $init is given, is a string, and starts with "?", remove the
        // first code point from $init.
        if (func_num_args() > 0) {
            if (is_string($init) && mb_substr($init, 0, 1) === '?') {
                $init = mb_substr($init, 1);
            }

            $this->init($init);
        }
    }

    public function __destruct()
    {
        $this->mUrl = null;
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

        foreach ($this->mList as $sequenceId => $pair) {
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
        $query->mUrl = $url;
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
                $name = Utils::DOMString($pair[0]);
                $value = Utils::DOMString($pair[1]);

                $this->mList[$this->mSequenceId] = [$name, $value];
                $this->mParams[$name][$this->mSequenceId++] = $value;
            }

            return;
        }

        if (is_object($init)) {
            foreach ($init as $name => $value) {
                $this->mList[$this->mSequenceId] = [$name, $value];
                $this->mParams[$name][$this->mSequenceId++] = Utils::DOMString(
                    $value
                );
            }

            return;
        }

        if (is_string($init)) {
            $pairs = URLUtils::urlencodedStringParser($init);

            foreach ($pairs as $pair) {
                $this->mList[$this->mSequenceId] = [$pair['name'], $pair['value']];
                $this->mParams[$pair['name']][$this->mSequenceId++] = $pair[
                    'value'
                ];
            }
        }
    }

    /**
     * Appends a new key -> value pair to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-append
     *
     * @param string $aName  The name of the key in the pair.
     *
     * @param string $aValue The value assigned to the key.
     */
    public function append($aName, $aValue)
    {
        $name = Utils::DOMString($aName);
        $value = Utils::DOMString($aValue);

        if (!is_string($name) || !is_string($value)) {
            return;
        }

        $this->mList[$this->mSequenceId] = [$name, $value];
        $this->mParams[$name][$this->mSequenceId++] = $value;
        $this->update();
    }

    /**
     * Deletes all occurances of pairs with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-delete
     *
     * @param  string $aName The name of the key to delete.
     */
    public function delete($aName)
    {
        $name = Utils::DOMString($aName);

        if (!is_string($name) || !isset($this->mParams[$name])) {
            return;
        }

        foreach ($this->mParams[$name] as $key => $value) {
            unset($this->mList[$key]);
        }

        unset($this->mParams[$name]);
        $this->update();
    }

    /**
     * Get the value of the first key -> value pair with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-get
     *
     * @param string $aName The name of the key whose value you want to retrive.
     *
     * @return string The value of the specified key.
     */
    public function get($aName)
    {
        $name = Utils::DOMString($aName);

        return is_string($name) && isset($this->mParams[$name]) ?
            reset($this->mParams[$name]) : null;
    }

    /**
     * Gets all key -> value pairs that has the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-getall
     *
     * @param string $aName The name of the key whose values you want to retrieve.
     *
     * @return string[] An array containing all the values of the specified key.
     */
    public function getAll($aName)
    {
        $name = Utils::DOMString($aName);

        return is_string($name) && isset($this->mParams[$name]) ?
            array_values($this->mParams[$name]) : [];
    }

    /**
     * Indicates whether or not a query string contains any keys with the
     * specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-has
     *
     * @param boolean $aName The key name you want to test if it exists.
     *
     * @return boolean         Returns true if the key exits, otherwise false.
     */
    public function has($aName)
    {
        $name = Utils::DOMString($aName);

        return is_string($name) && isset($this->mParams[$name]);
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
     * @param string $aName The name of the key you want to modify the value of.
     *
     * @param string $aValue The value you want to associate with the key name.
     */
    public function set($aName, $aValue)
    {
        $name = Utils::DOMString($aName);
        $value = Utils::DOMString($aValue);

        if (!is_string($name) || !is_string($value)) {
            return;
        }

        if (isset($this->mParams[$name])) {
            for ($i = count($this->mParams[$name]) - 1; $i > 0; $i--) {
                end($this->mParams[$name]);
                unset($this->mList[key($this->mParams[$name])]);
                array_pop($this->mParams[$name]);
            }

            reset($this->mParams[$name]);
            $id = key($this->mParams[$name]);
            $this->mList[$id][1] = $value;
            $this->mParams[$name][$id] = $value;
        } else {
            // Append the value
            $this->mList[$this->mSequenceId] = [$name, $value];
            $this->mParams[$name][$this->mSequenceId++] = $value;
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

        foreach ($this->mList as $sequenceId => $pair) {
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

        $this->mList = $list;
        $this->mParams = $params;
        $this->mSequenceId = $sequenceIdx;
        $this->update();
    }

    /**
     * Returns an iterator in the form of string[][].
     */
    public function getIterator()
    {
        return new ArrayIterator(array_values($this->mList));
    }

    /**
     * Mutates the the list of query parameters without going through the
     * public API.
     *
     * @internal
     *
     * @param  array|null $aList A list of name -> value pairs to be added to
     *     the list or null to empty the list.
     */
    public function _mutateList(array $aList = null)
    {
        $this->mList = array();
        $this->mParams = array();
        $this->mSequenceId = 0;

        if (is_array($aList)) {
            foreach ($aList as $pair) {
                $this->mList[$this->mSequenceId] = [
                    $pair['name'],
                    $pair['value']
                ];
                $this->mParams[$pair['name']][$this->mSequenceId++] = $pair[
                    'value'
                ];
            }
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
        if ($this->mUrl) {
            $this->mUrl->setQuery($this->__toString());
        }
    }
}
