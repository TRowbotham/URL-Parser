<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use ArrayIterator;
use IteratorAggregate;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\Support\EncodingHelper;

use function array_column;
use function array_filter;
use function array_splice;
use function count;
use function explode;
use function mb_ord;
use function mb_str_split;
use function rawurldecode;
use function rawurlencode;
use function str_contains;
use function str_replace;
use function strlen;
use function usort;

/**
 * @implements \IteratorAggregate<int, array{name: string, value: string}>
 */
class QueryList implements IteratorAggregate
{
    private const LEAD_OFFSET = 0xD800 - (0x10000 >> 10);

    /**
     * @var array<string, bool>
     */
    private $cache;

    /**
     * @var array<int, array{name: string, value: string}>
     */
    private $list;

    /**
     * @param array<int, array{name: string, value: string}> $list
     */
    public function __construct(array $list = [])
    {
        $this->list = $list;
        $this->cache = [];
    }

    /**
     * Decodes a application/x-www-form-urlencoded string and returns the decoded pairs as a list.
     *
     * Note: A legacy server-oriented implementation might have to support encodings other than
     * UTF-8 as well as have special logic for tuples of which the name is `_charset_`. Such logic
     * is not described here as only UTF-8' is conforming.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-parser
     *
     * @return self
     */
    public static function fromString(string $input): self
    {
        // Let sequences be the result of splitting input on 0x26 (&).
        $sequences = explode('&', $input);

        // Let output be an initially empty list of name-value tuples where both name and value
        // hold a string.
        $output = new self();

        foreach ($sequences as $bytes) {
            if ($bytes === '') {
                continue;
            }

            // If bytes contains a 0x3D (=), then let name be the bytes from the start of bytes up
            // to but excluding its first 0x3D (=), and let value be the bytes, if any, after the
            // first 0x3D (=) up to the end of bytes. If 0x3D (=) is the first byte, then name will
            // be the empty byte sequence. If it is the last, then value will be the empty byte
            // sequence. Otherwise, let name have the value of bytes and let value be the empty byte
            // sequence.
            $name = $bytes;
            $value = '';

            if (str_contains($bytes, '=')) {
                [$name, $value] = explode('=', $bytes, 2);
            }

            // Replace any 0x2B (+) in name and value with 0x20 (SP).
            [$name, $value] = str_replace('+', "\x20", [$name, $value]);

            // Let nameString and valueString be the result of running UTF-8
            // decode without BOM on the percent decoding of name and value,
            // respectively.
            $output->append(
                Utf8String::transcode(rawurldecode($name), 'utf-8', 'utf-8'),
                Utf8String::transcode(rawurldecode($value), 'utf-8', 'utf-8')
            );
        }

        return $output;
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
     * Determines if a name-value pair with name $name exists in the collection.
     */
    public function contains(string $name): bool
    {
        return isset($this->cache[$name]);
    }

    /**
     * Returns a filtered array based on the given callback.
     *
     * @return array<int, array<string, string>>
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->list, $callback);
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
     * @return \ArrayIterator<int, array{name: string, value: string}>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->list);
    }

    /**
     * @return array{name: string, value: string}|null
     */
    public function getTupleAt(int $index): ?array
    {
        return $this->list[$index] ?? null;
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

        if ($prevIndex === null) {
            return;
        }

        $this->list[$prevIndex]['value'] = $value;
    }

    /**
     * Sorts the collection by code units and preserves the relative positioning
     * of name-value pairs.
     */
    public function sort(): void
    {
        $temp = [];

        // Add information about the relative position of each name-value pair.
        foreach ($this->list as $pair) {
            $codeUnits = $this->convertToCodeUnits($pair['name']);
            $temp[] = ['original' => $pair, 'codeUnits' => $codeUnits, 'length' => count($codeUnits)];
        }

        // Sorting priority overview:
        //
        // Each string is compared code unit by code unit against each other.
        //
        // 1) If the two strings have different lengths, and the strings are equal up to the end of
        //    the shortest string, then the shorter of the two strings will be moved up in the
        //    array. (e.g. The string "aa" will come before the string "aaa".)
        // 2) If the value of the code units differ, the character with the lower code unit will be
        //    moved up in the array. (e.g. "🌈" will come before "ﬃ". Although "🌈" has a code
        //    point value of 127,752 that is greater than the "ﬃ" code point value of 64,259, "🌈"
        //    is split in to 2 code units and it's first code unit has a value of 55,356, which is
        //    less than the "ﬃ" single code unit value of 64,259.)
        // 3) If the two strings are considered equal, then they are sorted by the relative
        //    position in which they appeared in the array. (e.g. The string "b=c&a=c&b=a&a=a"
        //    becomes "a=c&a=a&b=c&b=a".)
        usort($temp, static function (array $a, array $b): int {
            if ($a['length'] === $b['length']) {
                return $a['codeUnits'] <=> $b['codeUnits'];
            }

            $i = 0;
            $comparison = 0;
            $aCodeUnits = $a['codeUnits'];
            $bCodeUnits = $b['codeUnits'];

            do {
                if (!isset($aCodeUnits[$i])) {
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

                if (!isset($bCodeUnits[$i])) {
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

                $comparison = $aCodeUnits[$i] <=> $bCodeUnits[$i];
                ++$i;
            } while ($comparison === 0);

            return $comparison;
        });

        $this->list = array_column($temp, 'original');
    }

    /**
     * Encodes the list of tuples as a valid application/x-www-form-urlencoded string.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-serializer
     *
     * @param string|null $encodingOverride (optional)
     */
    public function toUrlencodedString(string $encodingOverride = null): string
    {
        $encoding = EncodingHelper::getOutputEncoding($encodingOverride) ?? 'utf-8';
        $output = '';

        foreach ($this->list as $key => $tuple) {
            $name = $this->urlencode(Utf8String::transcode($tuple['name'], $encoding, 'utf-8'));
            $value = $this->urlencode(Utf8String::transcode($tuple['value'], $encoding, 'utf-8'));

            if ($key > 0) {
                $output .= '&';
            }

            $output .= $name . '=' . $value;
        }

        return $output;
    }

    /**
     * @see https://www.unicode.org/faq/utf_bom.html?source=post_page---------------------------#utf16-4
     *
     * @param string $input
     *
     * @return list<list<int>>
     */
    private function convertToCodeUnits(string $input): array
    {
        $codeUnits = [];

        foreach (mb_str_split($input, 1, 'utf-8') as $strCodePoint) {
            $codePoint = mb_ord($strCodePoint, 'utf-8');

            // Code points less than 0x10000 are part of the Basic Multilingual Plane and are
            // represented by a single code unit that is equal to its code point. Use 0 as the low
            // surrogate as the <=> operator compares array size first and values second.
            $codeUnits[] = $codePoint < 0x10000
                ? [$codePoint, 0]
                : [self::LEAD_OFFSET + ($codePoint >> 10), 0xDC00 + ($codePoint & 0x3FF)];
        }

        return $codeUnits;
    }

    /**
     * Encodes a string to be a valid application/x-www-form-urlencoded string.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-byte-serializer
     */
    private function urlencode(string $input): string
    {
        $output = '';
        $length = strlen($input);

        for ($i = 0; $i < $length; ++$i) {
            if ($input[$i] === "\x20") {
                $output .= '+';
            } elseif (
                $input[$i] === "\x2A"
                || $input[$i] === "\x2D"
                || $input[$i] === "\x2E"
                || ($input[$i] >= "\x30" && $input[$i] <= "\x39")
                || ($input[$i] >= "\x41" && $input[$i] <= "\x5A")
                || $input[$i] === "\x5F"
                || ($input[$i] >= "\x61" && $input[$i] <= "\x7A")
            ) {
                $output .= $input[$i];
            } else {
                $output .= rawurlencode($input[$i]);
            }
        }

        return $output;
    }
}
