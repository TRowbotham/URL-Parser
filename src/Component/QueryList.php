<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use ArrayIterator;
use IntlBreakIterator;
use IteratorAggregate;
use Rowbot\URL\String\Utf8String;

use function array_filter;
use function array_splice;
use function count;
use function explode;
use function rawurldecode;
use function rawurlencode;
use function strlen;
use function strpos;
use function str_replace;

/**
 * @implements \IteratorAggregate<int, array{name: string, value: string}>
 */
class QueryList implements IteratorAggregate
{
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

            if (strpos($bytes, '=') !== false) {
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

        // Remove the relative positioning information so that $temp only contains the sorted
        // name-value pairs.
        $this->list = [];

        foreach ($temp as [$relativePosition, $tuple]) {
            $this->list[] = $tuple;
        }
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

    /**
     * Encodes the list of tuples as a valid application/x-www-form-urlencoded string.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-serializer
     *
     * @param string|null $encodingOverride (optional)
     */
    public function toUrlencodedString(string $encodingOverride = null): string
    {
        $encoding = 'utf-8';

        if ($encodingOverride !== null) {
            $encoding = $encodingOverride;
        }

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
}
