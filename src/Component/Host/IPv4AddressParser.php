<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Psr\Log\LogLevel;
use Rowbot\URL\Component\Host\Math\NumberFactory;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringListInterface;
use Rowbot\URL\String\USVStringInterface;

use function array_pop;
use function array_reduce;
use function array_slice;
use function count;
use function strlen;
use function strspn;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4-parser
 */
class IPv4AddressParser
{
    public static function parse(ParserContext $context, USVStringInterface $input): IPv4Address|false
    {
        // 2. Let parts be the result of strictly splitting input on U+002E (.).
        $parts = $input->split('.');
        $count = $parts->count();

        // 3. If the last item in parts is the empty string, then:
        if ($parts->last()->isEmpty()) {
            // 3.1. Validation error.
            $context->logger?->notice('ipv4-last-part-empty', [
                'input'  => (string) $input,
                'column' => array_reduce(
                    array_slice([...$parts], 0, $count - 1),
                    static fn (int $carry, USVStringInterface $part): int => $carry + $part->length(),
                    0
                ),
            ]);

            // 3.2. If parts’s size is greater than 1, then remove the last item from parts.
            if ($count > 1) {
                $parts->pop();
                --$count;
            }
        }

        // 4. If parts’s size is greater than 4, validation error, return failure.
        if ($count > 4) {
            $context->logger?->warning('ipv4-too-many-parts', [
                'input'  => (string) $input,
                'column' => array_reduce(
                    array_slice([...$parts], 0, 4),
                    static fn (int $carry, USVStringInterface $part): int => $carry + $part->length(),
                    4
                ),
            ]);

            return false;
        }

        // 5. Let numbers be an empty list.
        $numbers = [];

        // 6. For each part of parts:
        foreach ($parts as $i => $part) {
            // 6.1. Let result be the result of parsing part.
            $result = self::parseIPv4Number($part);

            // 6.2. If result is failure, validation error, return failure.
            if ($result === false) {
                $context->logger?->warning('ipv4-invalid-radix-digit', [
                    'input'        => (string) $input,
                    'column_range' => self::getColumnRange($i, $parts),
                ]);

                return false;
            }

            // 6.3. If result[1] is true, then set validationError to true.
            if ($result[1] === true) {
                // Validation error.
                $context->logger?->notice('unexpected-non-decimal-number', [
                    'input'        => (string) $input,
                    'column_range' => self::getColumnRange($i, $parts),
                ]);
            }

            // 6.4. Append result[0] to numbers.
            $numbers[] = $result[0];
        }

        $size = count($numbers);
        $sizeMinusOne = $size - 1;
        $partTooLarge = false;

        // 8. If any item in numbers is greater than 255, validation error.
        foreach ($numbers as $i => $number) {
            if ($number->isGreaterThan(255)) {
                $level = LogLevel::NOTICE;

                if ($i < $sizeMinusOne) {
                    $partTooLarge = true;
                    $level = LogLevel::WARNING;
                }

                $context->logger?->log($level, 'ipv4-part-out-of-range', [
                    'input'        => (string) $input,
                    'column_range' => self::getColumnRange($i, $parts),
                ]);

                break;
            }
        }

        // 9. If any but the last item in numbers is greater than 255, then return failure.
        if ($partTooLarge) {
            return false;
        }

        $limit = NumberFactory::createNumber(256, 10)->pow(5 - $size);

        // 10. If the last item in numbers is greater than or equal to 256 ** (5 − numbers’s size), validation error,
        // return failure.
        if ($numbers[$sizeMinusOne]->isGreaterThanOrEqualTo($limit)) {
            $context->logger?->warning('ipv4-part-out-of-range', [
                'input'        => (string) $input,
                'column_range' => [$input->length() - $parts->last()->length() + 1, $input->length()],
            ]);

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

    public static function endsInIPv4Number(USVStringInterface $input): bool
    {
        // 1. Let parts be the result of strictly splitting input on U+002E (.).
        $parts = $input->split('.');

        // 2. If the last item in parts is the empty string, then:
        if ($parts->last()->isEmpty()) {
            // 2.1. If parts’s size is 1, then return false.
            if ($parts->count() === 1) {
                return false;
            }

            // 3.1. Remove the last item from parts.
            $parts->pop();
        }

        // 3. Let last be the last item in parts.
        $last = $parts->last();

        // 4. If parsing last as an IPv4 number does not return failure, then return true.
        if (self::parseIPv4Number($last) !== false) {
            return true;
        }

        // 5. If last is non-empty and contains only ASCII digits, then return true.
        // 6. Return false.
        return !$last->isEmpty() && strspn((string) $last, CodePoint::ASCII_DIGIT_MASK) === $last->length();
    }

    /**
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @return array{0: \Rowbot\URL\Component\Host\Math\NumberInterface, 1: bool}|false
     */
    private static function parseIPv4Number(USVStringInterface $input): array|false
    {
        // 1. If input is the empty string, then return failure.
        if ($input->isEmpty()) {
            return false;
        }

        // 2. Let validationError be false.
        $validationError = false;

        // 3. Let R be 10.
        $radix = 10;

        if ($input->length() > 1) {
            // 4. If input contains at least two code points and the first two code points are either "0x" or "0X",
            // then:
            if ($input->startsWith('0x') || $input->startsWith('0X')) {
                // 4.1. Set validationError to true.
                $validationError = true;

                // 4.2. Remove the first two code points from input.
                $input = $input->substr(2);

                // 4.3. Set R to 16.
                $radix = 16;

            // 5. Otherwise, if input contains at least two code points and the first code point is U+0030 (0), then:
            } elseif ($input->startsWith('0')) {
                // 5.1. Set validationError to true.
                $validationError = true;

                // 5.2. Remove the first code point from input.
                $input = $input->substr(1);

                // 5.3. Set R to 8.
                $radix = 8;
            }
        }

        // 6. If input is the empty string, then return 0.
        if ($input->isEmpty()) {
            return [NumberFactory::createNumber(0, 10), $validationError];
        }

        $s = (string) $input;
        $length = strlen($s);

        // 7. If input contains a code point that is not a radix-R digit, then return failure.
        if (
            ($radix === 10 && strspn($s, CodePoint::ASCII_DIGIT_MASK) !== $length)
            || ($radix === 16 && strspn($s, CodePoint::HEX_DIGIT_MASK) !== $length)
            || ($radix === 8 && strspn($s, CodePoint::OCTAL_DIGIT_MASK) !== $length)
        ) {
            return false;
        }

        // 8. Let output be the mathematical integer value that is represented by input in radix-R notation, using ASCII
        // hex digits for digits with values 0 through 15.
        // 9. Return (output, validationError).
        return [NumberFactory::createNumber($s, $radix), $validationError];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function getColumnRange(int $i, StringListInterface $parts): array
    {
        $partsArray = array_slice([...$parts], 0, $i + 1);
        $offset = array_reduce(
            $partsArray,
            static fn (int $carry, USVStringInterface $part): int => $carry + $part->length(),
            $i
        );

        return [$offset - $partsArray[$i]->length() + 1, $offset];
    }
}
