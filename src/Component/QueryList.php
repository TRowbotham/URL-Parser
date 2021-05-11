<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use ArrayIterator;
use IteratorAggregate;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\Support\EncodingHelper;

use function array_filter;
use function array_splice;
use function count;
use function explode;
use function ord;
use function rawurldecode;
use function rawurlencode;
use function str_replace;
use function strlen;
use function strpos;
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
     * Appends a new name-value pair to the list.
     */
    public function append(string $name, string $value): void
    {
        $this->list[] = ['name' => $name, 'value' => $value];
        $this->cache[$name] = true;
    }

    /**
     * @see https://www.unicode.org/faq/utf_bom.html?source=post_page---------------------------#utf16-4
     *
     * @return array{0: int, 1: int}
     */
    private function computeCodeUnits(int $codePoint): array
    {
        // Code points less than 0x10000 are part of the Basic Multilingual Plane and are
        // represented by a single code unit that is equal to its code point. Use 0 as the low
        // surrogate as the <=> operator compares array size first and values second.
        if ($codePoint < 0x10000) {
            return [$codePoint, 0];
        }

        return [self::LEAD_OFFSET + ($codePoint >> 10), 0xDC00 + ($codePoint & 0x3FF)];
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

        // Sorting priority overview:
        //
        // Each string is compared character by character against each other.
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
        usort($temp, function (array $a, array $b): int {
            $iter1 = $this->utf8Decode($a[1]['name']);
            $iter2 = $this->utf8Decode($b[1]['name']);
            $i = 0;

            while (true) {
                $aIsValid = isset($iter1[$i]);
                $bIsValid = isset($iter2[$i]);

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

                $aCodeUnits = $this->computeCodeUnits($iter1[$i]);
                $bCodeUnits = $this->computeCodeUnits($iter2[$i]);
                $cmp = $aCodeUnits <=> $bCodeUnits;

                // We only want to return if the result is not equal, as equal results must be
                // sorted by relative position.
                if ($cmp !== 0) {
                    return $cmp;
                }

                ++$i;
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
     * Takes a UTF-8 encoded string and converts it into a series of integer code points. Any
     * invalid byte sequences will be replaced by a U+FFFD replacement code point.
     *
     * @see https://encoding.spec.whatwg.org/#utf-8-decoder
     *
     * @return list<int>
     */
    private function utf8Decode(string $input): array
    {
        $bytesSeen = 0;
        $bytesNeeded = 0;
        $lowerBoundary = 0x80;
        $upperBoundary = 0xBF;
        $codePoint = 0;
        $codePoints = [];
        $length = strlen($input);

        for ($i = 0; $i < $length; ++$i) {
            $byte = ord($input[$i]);

            if ($bytesNeeded === 0) {
                if ($byte >= 0x00 && $byte <= 0x7F) {
                    $codePoints[] = $byte;

                    continue;
                }

                if ($byte >= 0xC2 && $byte <= 0xDF) {
                    $bytesNeeded = 1;
                    $codePoint = $byte & 0x1F;
                } elseif ($byte >= 0xE0 && $byte <= 0xEF) {
                    if ($byte === 0xE0) {
                        $lowerBoundary = 0xA0;
                    } elseif ($byte === 0xED) {
                        $upperBoundary = 0x9F;
                    }

                    $bytesNeeded = 2;
                    $codePoint = $byte & 0xF;
                } elseif ($byte >= 0xF0 && $byte <= 0xF4) {
                    if ($byte === 0xF0) {
                        $lowerBoundary = 0x90;
                    } elseif ($byte === 0xF4) {
                        $upperBoundary = 0x8F;
                    }

                    $bytesNeeded = 3;
                    $codePoint = $byte & 0x7;
                } else {
                    $codePoints[] = 0xFFFD;
                }

                continue;
            }

            if ($byte < $lowerBoundary || $byte > $upperBoundary) {
                $codePoint = 0;
                $bytesNeeded = 0;
                $bytesSeen = 0;
                $lowerBoundary = 0x80;
                $upperBoundary = 0xBF;
                --$i;
                $codePoints[] = 0xFFFD;

                continue;
            }

            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $codePoint = ($codePoint << 6) | ($byte & 0x3F);

            if (++$bytesSeen !== $bytesNeeded) {
                continue;
            }

            $codePoints[] = $codePoint;
            $codePoint = 0;
            $bytesNeeded = 0;
            $bytesSeen = 0;
        }

        // String unexpectedly ended, so append a U+FFFD code point.
        if ($bytesNeeded !== 0) {
            $codePoints[] = 0xFFFD;
        }

        return $codePoints;
    }
}
