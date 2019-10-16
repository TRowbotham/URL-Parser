<?php

declare(strict_types=1);

namespace Rowbot\URL;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use Rowbot\URL\Exception\TypeError;
use UConverter;

use function array_column;
use function count;
use function func_num_args;
use function gettype;
use function is_array;
use function is_iterable;
use function is_object;
use function is_scalar;
use function is_string;
use function mb_substr;
use function method_exists;

/**
 * An object containing a list of all URL query parameters. This allows you to manipulate a URL's
 * query string in a granular manner.
 *
 * @see https://url.spec.whatwg.org/#urlsearchparams
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
 */
class URLSearchParams implements Iterator
{
    use URLFormEncoded;

    /**
     * @var \Rowbot\URL\QueryList
     */
    private $list;

    /**
     * @var \Rowbot\URL\URLRecord|null
     */
    private $url;

    /**
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-urlsearchparams
     *
     * @param self|iterable<int, array{0: string, 1: string}>|object|string $init (optional)
     */
    public function __construct($init = '')
    {
        $this->list = new QueryList();
        $this->url = null;

        // If $init is given, is a string, and starts with "?", remove the
        // first code point from $init.
        if (func_num_args() > 0) {
            if (is_scalar($init) || is_object($init) && method_exists($init, '__toString')) {
                $init = UConverter::transcode((string) $init, 'UTF-8', 'UTF-8');
            }

            if (is_string($init) && mb_substr($init, 0, 1, 'UTF-8') === '?') {
                $init = mb_substr($init, 1, null, 'UTF-8');
            }

            $this->init($init);
        }
    }

    /**
     * Appends a new name-value pair to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-append
     *
     * @param string $name  The name of the key in the pair.
     * @param string $value The value assigned to the key.
     */
    public function append(string $name, string $value): void
    {
        $this->list->append(
            UConverter::transcode($name, 'UTF-8', 'UTF-8'),
            UConverter::transcode($value, 'UTF-8', 'UTF-8')
        );
        $this->update();
    }

    /**
     * Clears the list of search params.
     *
     * @internal
     */
    public function clear(): void
    {
        $this->list->clear();
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-new
     *
     * @internal
     *
     * @param string                $init The query string or another URLSearchParams object.
     * @param \Rowbot\URL\URLRecord $url  The associated URLRecord object.
     */
    public static function create($init, URLRecord $url): self
    {
        $query = new self();
        $query->url = $url;
        $query->init($init);

        return $query;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function current(): array
    {
        $current = $this->list->current();

        return [$current['name'], $current['value']];
    }

    /**
     * Deletes all occurances of pairs with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-delete
     *
     * @param string $name The name of the key to delete.
     */
    public function delete(string $name): void
    {
        $this->list->remove(UConverter::transcode($name, 'UTF-8', 'UTF-8'));
        $this->update();
    }

    /**
     * Get the value of the first name-value pair with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-get
     *
     * @param string $name The name of the key whose value you want to retrive.
     *
     * @return string|null The value of the specified key.
     */
    public function get(string $name): ?string
    {
        return $this->list->first(UConverter::transcode($name, 'UTF-8', 'UTF-8'));
    }

    /**
     * Gets all name-value pairs that has the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-getall
     *
     * @param string $name The name of the key whose values you want to retrieve.
     *
     * @return array<int, string> An array containing all the values of the specified key.
     */
    public function getAll(string $name): array
    {
        $name = UConverter::transcode($name, 'UTF-8', 'UTF-8');

        return array_column($this->list->filter(static function (array $pair) use ($name): bool {
            return $pair['name'] === $name;
        }), 'value');
    }

    /**
     * Indicates whether or not a query string contains any keys with the specified key name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-has
     *
     * @param string $name The key name you want to test if it exists.
     *
     * @return bool Returns true if the key exits, otherwise false.
     */
    public function has(string $name): bool
    {
        return $this->list->contains(UConverter::transcode($name, 'UTF-8', 'UTF-8'));
    }

    /**
     * @internal
     *
     * @param self|iterable<int, array{0: string, 1: string}>|object|string $input
     *
     * @throws \InvalidArgumentException
     * @throws \Rowbot\URL\Exception\TypeError
     */
    private function init($input): void
    {
        if (is_iterable($input)) {
            $this->initIterator($input);

            return;
        }

        if (is_object($input)) {
            $this->initObject($input);

            return;
        }

        if (is_string($input)) {
            $this->initString($input);
        }
    }

    private function initIterator(iterable $input): void
    {
        foreach ($input as $pair) {
            // Try to catch cases where $pair isn't countable or $pair is
            // countable, but isn't a valid sequence, such as:
            //
            // class CountableClass implements \Countable
            // {
            //     public function count()
            //     {
            //         return 2;
            //     }
            // }
            //
            // $s = new \Rowbot\URL\URLSearchParams([new CountableClass()]);
            //
            // or:
            //
            // $a = new \ArrayObject(['x', 'y']);
            // $s = new \Rowbot\URL\URLSearchParams($a);
            //
            // while still allowing things like:
            //
            // $a = new \ArrayObject(new \ArrayObject(['x', 'y']));
            // $s = new \Rowbot\URL\URLSearchParams($a);'
            if (
                !is_array($pair)
                && (!$pair instanceof ArrayAccess || !$pair instanceof Countable)
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Expected a valid sequence such as an Array or Object that implements both the '
                    . 'ArrayAccess and Countable interfaces. %s found instead.',
                    gettype($pair)
                ));
            }

            if (count($pair) !== 2) {
                throw new TypeError(sprintf(
                    'Expected sequence with excatly 2 items. Sequence contained %d items.',
                    count($pair)
                ));
            }
        }

        foreach ($input as $pair) {
            $this->list->append(
                UConverter::transcode($pair[0], 'UTF-8', 'UTF-8'),
                UConverter::transcode($pair[1], 'UTF-8', 'UTF-8')
            );
        }
    }

    /**
     * @param object $input
     */
    private function initObject($input): void
    {
        foreach ($input as $name => $value) {
            $this->append(
                UConverter::transcode($name, 'UTF-8', 'UTF-8'),
                UConverter::transcode($value, 'UTF-8', 'UTF-8')
            );
        }
    }

    private function initString(string $input): void
    {
        $pairs = $this->urldecodeString($input);

        foreach ($pairs as $pair) {
            $this->list->append($pair['name'], $pair['value']);
        }
    }

    public function key(): int
    {
        return $this->list->key();
    }

    /**
     * Mutates the the list of query parameters without going through the
     * public API.
     *
     * @internal
     *
     * @param array<int, array{name: string, value: string}> $list A list of name-value pairs to be
     *                                                             added to the list.
     */
    public function modify(array $list): void
    {
        $this->list->update($list);
    }

    public function next(): void
    {
        $this->list->next();
    }

    public function rewind(): void
    {
        $this->list->rewind();
    }

    /**
     * Sets the value of the specified key name. If multiple pairs exist with the same key name it
     * will set the value for the first occurance of the key in the query string and all other
     * occurances will be removed from the query string.  If the key does not already exist in the
     * query string, it will be added to the end of the query string.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-set
     *
     * @param string $name  The name of the key you want to modify the value of.
     * @param string $value The value you want to associate with the key name.
     */
    public function set(string $name, string $value): void
    {
        $name = UConverter::transcode($name, 'UTF-8', 'UTF-8');
        $value = UConverter::transcode($value, 'UTF-8', 'UTF-8');

        if ($this->list->contains($name)) {
            $this->list->set($name, $value);
        } else {
            $this->list->append($name, $value);
        }

        $this->update();
    }

    /**
     * Sets the associated url record.
     *
     * @internal
     *
     * @param \Rowbot\URL\URLRecord $url
     */
    public function setUrl(URLRecord $url): void
    {
        $this->url = $url;
    }

    /**
     * Sorts the list of search params by their names by comparing their code unit values,
     * preserving the relative order between pairs with the same name.
     *
     * @see https://url.spec.whatwg.org/#dom-urlsearchparams-sort
     */
    public function sort(): void
    {
        $this->list->sort();
        $this->update();
    }

    public function toString(): string
    {
        return $this->urlencodeList($this->list->all());
    }

    /**
     * Set's the associated URL object's query to the serialization of URLSearchParams.
     *
     * @see https://url.spec.whatwg.org/#concept-urlsearchparams-update
     *
     * @internal
     */
    protected function update(): void
    {
        if ($this->url === null) {
            return;
        }

        $query = $this->urlencodeList($this->list->all());

        if ($query === '') {
            $query = null;
        }

        $this->url->query = $query;
    }

    public function valid(): bool
    {
        return $this->list->valid();
    }

    public function __clone()
    {
        $this->list = clone $this->list;

        // Null out the url in-case someone tries cloning the object returned by
        // the URL::searchParams attribute.
        $this->url = null;
    }

    /**
     * Returns all name-value pairs stringified in the correct order.
     *
     * @return string The query string.
     */
    public function __toString(): string
    {
        return $this->urlencodeList($this->list->all());
    }
}
