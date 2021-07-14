<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Math\NumberFactory;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;

use function array_pop;
use function count;
use function strlen;
use function strspn;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4-parser
 */
class IPv4AddressParser
{
    /**
     * @return \Rowbot\URL\String\USVStringInterface|\Rowbot\URL\Component\Host\IPv4Address|false
     */
    public static function parse(USVStringInterface $input)
    {
        // 1. Let validationError be false.
        //
        // This uses validationError to track validation errors to avoid reporting them before we are confident we want
        // to parse input as an IPv4 address as the host parser almost always invokes the IPv4 parser.
        $validationError = false;

        // 2. Let parts be the result of strictly splitting input on U+002E (.).
        $parts = $input->split('.');
        $count = $parts->count();

        // 3. If the last item in parts is the empty string, then:
        if ($parts->last()->isEmpty()) {
            // 3.1. Set validationError to true.
            $validationError = true;

            // 3.2. If parts’s size is greater than 1, then remove the last item from parts.
            if ($count > 1) {
                $parts->pop();
                --$count;
            }
        }

        // 4. If parts’s size is greater than 4, then return input.
        if ($count > 4) {
            return $input;
        }

        // 5. Let numbers be an empty list.
        $numbers = [];

        // 6. For each part of parts:
        foreach ($parts as $part) {
            // 6.1. If part is the empty string, then return input.
            if ($part->isEmpty()) {
                return $input;
            }

            // 6.2. Let result be the result of parsing part.
            $result = self::parseIPv4Number($part);

            // 6.3. If result is failure, then return input.
            if ($result === false) {
                return $input;
            }

            // 6.4. If result[1] is true, then set validationError to true.
            if ($result[1] === true) {
                $validationError = $result[1];
            }

            // 6.5. Append result[0] to numbers.
            $numbers[] = $result[0];
        }

        // 7. If validationError is true, validation error.
        //
        // At this point each part was parsed into a number and input will be treated as an IPv4 address (or failure).
        // And therefore error reporting resumes.
        if ($validationError) {
            // Validation error.
        }

        // 8. If any item in numbers is greater than 255, validation error.
        foreach ($numbers as $number) {
            if ($number->isGreaterThan(255)) {
                break;
            }
        }

        $size = count($numbers);

        // 9. If any but the last item in numbers is greater than 255, then return failure.
        for ($i = 0; $i < $size - 1; ++$i) {
            if ($numbers[$i]->isGreaterThan(255)) {
                return false;
            }
        }

        $limit = NumberFactory::createNumber(256, 10)->pow(5 - $size);

        // 10. If the last item in numbers is greater than or equal to 256 ** (5 − numbers’s size), validation error,
        // return failure.
        if ($numbers[$size - 1]->isGreaterThanOrEqualTo($limit)) {
            return false;
        }

        /**
         * 11. Let ipv4 be the last item in numbers.
         * 12. Remove the last item from numbers.
         *
         * @var \Rowbot\URL\Component\Host\Math\NumberInterface $ipv4
         */
        $ipv4 = array_pop($numbers);

        // 13. Let counter be 0.
        $counter = 0;

        // 14. For each n of numbers:
        foreach ($numbers as $number) {
            // 14.1. Increment ipv4 by n × 256 ** (3 − counter).
            $ipv4 = $ipv4->plus($number->multipliedBy(256 ** (3 - $counter)));

            // 14.2. Increment counter by 1.
            ++$counter;
        }

        // 15. Return ipv4.
        return new IPv4Address((string) $ipv4);
    }

    /**
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @return array{0: \Rowbot\URL\Component\Host\Math\NumberInterface, 1: bool}|false
     */
    private static function parseIPv4Number(USVStringInterface $input)
    {
        // 1. Let validationError be false.
        $validationError = false;

        // 2. Let R be 10.
        $radix = 10;

        if ($input->length() > 1) {
            // 3. If input contains at least two code points and the first two code points are either "0x" or "0X",
            // then:
            if ($input->startsWith('0x') || $input->startsWith('0X')) {
                // 3.1. Set validationError to true.
                $validationError = true;

                // 3.2. Remove the first two code points from input.
                $input = $input->substr(2);

                // 3.3. Set R to 16.
                $radix = 16;

            // 4. Otherwise, if input contains at least two code points and the first code point is U+0030 (0), then:
            } elseif ($input->startsWith('0')) {
                // 4.1. Set validationError to true.
                $validationError = true;

                // 4.2. Remove the first code point from input.
                $input = $input->substr(1);

                // 4.3. Set R to 8.
                $radix = 8;
            }
        }

        // 5. If input is the empty string, then return 0.
        if ($input->isEmpty()) {
            return [NumberFactory::createNumber(0, 10), $validationError];
        }

        $s = (string) $input;
        $length = strlen($s);

        // 6. If input contains a code point that is not a radix-R digit, then return failure.
        if (
            ($radix === 10 && strspn($s, CodePoint::ASCII_DIGIT_MASK) !== $length)
            || ($radix === 16 && strspn($s, CodePoint::HEX_DIGIT_MASK) !== $length)
            || ($radix === 8 && strspn($s, CodePoint::OCTAL_DIGIT_MASK) !== $length)
        ) {
            return false;
        }

        // 7. Let output be the mathematical integer value that is represented by input in radix-R notation, using ASCII
        // hex digits for digits with values 0 through 15.
        // 8. Return (output, validationError).
        return [NumberFactory::createNumber($s, $radix), $validationError];
    }
}
