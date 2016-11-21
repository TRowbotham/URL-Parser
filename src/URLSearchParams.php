<?php
namespace phpjs\urls;

use phpjs\Utils;

/**
 * An object containing a list of all URL query parameters.  This allows you to
 * manipulate a URL's query string in a granular manner.
 *
 * @see https://url.spec.whatwg.org/#urlsearchparams
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
 */
class URLSearchParams implements \Iterator
{
    private $mIndex;
    private $mParams;
    private $mPosition;
    private $mSequenceId;
    private $mUrl;

    public function __construct($aInit = '')
    {
        $init = Utils::DOMString($aInit);
        $this->mPosition = 0;
        $this->mUrl = null;

        if (is_string($init)) {
            $this->mIndex = [];
            $this->mParams = [];
            $this->mSequenceId = 0;

            // If init is given, is a string, and starts with "?", remove the
            // first code point from init.
            if ($init !== '' && mb_substr($init, 0, 1) === '?') {
                $init = mb_substr($init, 1);
            }

            $pairs = URLUtils::urlencodedStringParser($init);

            foreach ($pairs as $pair) {
                $this->mIndex[$this->mSequenceId] = $pair['name'];
                $this->mParams[$pair['name']][$this->mSequenceId++] =
                    $pair['value'];
            }
        } elseif ($init instanceof self) {
            $this->mIndex = $init->mIndex;
            $this->mParams = $init->mParams;
            $this->mSequenceId = $init->mSequenceId;
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
        $list = array();

        foreach ($this->mIndex as $sequenceId => $name) {
            $list[] = [
                'name' => $name,
                'value' => $this->mParams[$name][$sequenceId]
            ];
        }

        return URLUtils::urlencodedSerializer($list);
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

        $this->mIndex[$this->mSequenceId] = $name;
        $this->mParams[$name][$this->mSequenceId++] = $value;
        $this->update();
    }

    /**
     * Returns an array containing the query parameters name as the first
     * index's value and the query parameters value as the second index's value.
     *
     * @return string[]
     */
    public function current()
    {
        $index = array_keys($this->mIndex);
        $sequenceId = $index[$this->mPosition];
        $name = $this->mIndex[$sequenceId];

        return array($name, $this->mParams[$name][$sequenceId]);
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
            unset($this->mIndex[$key]);
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
     * Returns the key of the current name -> value pair of query parameters in
     * the iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->mPosition;
    }

    /**
     * Moves the the iterator to the next name -> value pair of query
     * parameters.
     */
    public function next()
    {
        $this->mPosition++;
    }

    /**
     * Rewinds the iterator back to the beginning position.
     */
    public function rewind()
    {
        $this->mPosition = 0;
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
                unset($this->mIndex[key($this->mParams[$name])]);
                array_pop($this->mParams[$name]);
            }

            reset($this->mParams[$name]);
            $this->mParams[$name][key($this->mParams[$name])] = $value;
        } else {
            // Append the value
            $this->mIndex[$this->mSequenceId] = $name;
            $this->mParams[$name][$this->mSequenceId++] = $value;
        }

        $this->update();
    }

    /**
     * Returns whether or not the iterator's current postion is a valid one.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->mPosition < count($this->mIndex);
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
        $this->mIndex = array();
        $this->mParams = array();
        $this->mSequenceId = 0;

        if (is_array($aList)) {
            foreach ($aList as $pair) {
                $this->mIndex[$this->mSequenceId] = $pair['name'];
                $this->mParams[$pair['name']][$this->mSequenceId++] =
                    $pair['value'];
            }
        }
    }

    /**
     * Set's the URLSearchParam's associated URL object.
     *
     * @internal
     *
     * @param URLRecord|null $aUrl The associated URL object.
     */
    public function _setUrl(URLRecord $aUrl = null)
    {
        $this->mUrl = $aUrl;
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
