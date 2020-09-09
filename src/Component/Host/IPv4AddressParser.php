<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Math\NumberFactory;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;

use function array_pop;
use function count;

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
        $validationError = false;
        $parts = $input->split('.');
        $count = $parts->count();

        if ($parts->last()->isEmpty()) {
            $validationError = true;

            if ($count > 1) {
                $parts->pop();
                --$count;
            }
        }

        if ($count > 4) {
            return $input;
        }

        $numbers = [];

        foreach ($parts as $part) {
            if ($part->isEmpty()) {
                return $input;
            }

            $result = self::parseIPv4Number($part);

            if ($result === false) {
                return $input;
            }

            if ($result[1] === true) {
                $validationError = $result[1];
            }

            $numbers[] = $result[0];
        }

        if ($validationError) {
            // Validation error.
        }

        foreach ($numbers as $number) {
            if ($number->isGreaterThan(255)) {
                // Validation error.
                break;
            }
        }

        $size = count($numbers);

        for ($i = 0; $i < $size - 1; ++$i) {
            if ($numbers[$i]->isGreaterThan(255)) {
                return false;
            }
        }

        $limit = NumberFactory::createNumber(256, 10)->pow(5 - $size);

        if ($numbers[$size - 1]->isGreaterThanOrEqualTo($limit)) {
            // Validation error.
            return false;
        }

        /** @var \Rowbot\URL\Component\Host\Math\NumberInterface $ipv4 */
        $ipv4 = array_pop($numbers);
        $counter = 0;

        foreach ($numbers as $number) {
            $ipv4 = $ipv4->plus($number->multipliedBy(256 ** (3 - $counter)));
            ++$counter;
        }

        return new IPv4Address((string) $ipv4);
    }

    /**
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @return array{0: \Rowbot\URL\Component\Host\Math\NumberInterface, 1: bool}|false
     */
    private static function parseIPv4Number(USVStringInterface $input)
    {
        $validationError = false;
        $radix = 10;

        if ($input->length() > 1) {
            if ($input->startsWith('0x') || $input->startsWith('0X')) {
                $validationError = true;
                $input = $input->substr(2);
                $radix = 16;
            } elseif ($input->startsWith('0')) {
                $validationError = true;
                $input = $input->substr(1);
                $radix = 8;
            }
        }

        if ($input->isEmpty()) {
            return [NumberFactory::createNumber(0, 10), $validationError];
        }

        if (
            ($radix === 10 && !self::isDecimal($input))
            || ($radix === 16 && !self::isHexadecimal($input))
            || ($radix === 8 && !self::isOctal($input))
        ) {
            return false;
        }

        return [NumberFactory::createNumber((string) $input, $radix), $validationError];
    }

    /**
     * Checks if the given input only contains ASCII decimal digits.
     */
    private static function isDecimal(USVStringInterface $input): bool
    {
        foreach ($input as $codePoint) {
            if (!CodePoint::isAsciiDigit($codePoint)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the given input only contains ASCII hex digits.
     */
    private static function isHexadecimal(USVStringInterface $input): bool
    {
        foreach ($input as $codePoint) {
            if (!CodePoint::isAsciiHexDigit($codePoint)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the given input contains only octal digits.
     */
    private static function isOctal(USVStringInterface $input): bool
    {
        foreach ($input as $codePoint) {
            if (!CodePoint::isAsciiOctalDigit($codePoint)) {
                return false;
            }
        }

        return true;
    }
}
