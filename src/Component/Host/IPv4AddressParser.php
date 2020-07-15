<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Exception\InvalidIPv4AddressException;
use Rowbot\URL\Component\Host\Exception\InvalidIPv4NumberException;
use Rowbot\URL\Component\Host\Math\NumberFactory;
use Rowbot\URL\Component\Host\Math\NumberInterface;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;

use function array_pop;
use function count;
use function sprintf;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4-parser
 */
class IPv4AddressParser
{
    /**
     * @var bool
     */
    private $validationError;

    public function __construct()
    {
        $this->validationError = false;
    }

    public function parse(USVStringInterface $input): IPv4Address
    {
        $this->validationError = false;
        $parts = $input->split('.');
        $count = $parts->count();

        if ($parts->last()->isEmpty()) {
            $this->validationError = true;

            if ($count > 1) {
                $parts->pop();
                --$count;
            }
        }

        if ($count > 4) {
            throw new InvalidIPv4AddressException(sprintf(
                'IPv4 address cannot contain more than 4 octets. %d octets were found.',
                $count
            ));
        }

        $numbers = [];

        foreach ($parts as $part) {
            if ($part->isEmpty()) {
                throw new InvalidIPv4AddressException('IPv4 octets cannot be an empty string.');
            }

            $numbers[] = $this->parseIPv4Number($part);
        }

        if ($this->validationError) {
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
                throw new InvalidIPv4NumberException(sprintf(
                    'Octet number %d had a value of %s, which exceeded the maximum valid size of 255.',
                    $i + 1,
                    (string) $numbers[$i]
                ));
            }
        }

        $limit = NumberFactory::createNumber(256, 10)->pow(5 - $size);

        if ($numbers[$size - 1]->isGreaterThanOrEqualTo($limit)) {
            // Validation error.
            throw new InvalidIPv4NumberException(sprintf(
                'The last octet had a value of %d, which exceeded the maximum valid size of %s.',
                (string) $numbers[$size - 1],
                (string) $limit
            ));
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
     */
    private function parseIPv4Number(USVStringInterface $input): NumberInterface
    {
        $radix = 10;

        if ($input->length() > 1) {
            if ($input->startsWith('0x') || $input->startsWith('0X')) {
                $this->validationError = true;
                $input = $input->substr(2);
                $radix = 16;
            } elseif ($input->startsWith('0')) {
                $this->validationError = true;
                $input = $input->substr(1);
                $radix = 8;
            }
        }

        if ($input->isEmpty()) {
            return NumberFactory::createNumber(0, 10);
        }

        if (
            ($radix === 10 && !$this->isDecimal($input))
            || ($radix === 16 && !$this->isHexadecimal($input))
            || ($radix === 8 && !$this->isOctal($input))
        ) {
            throw new InvalidIPv4AddressException(sprintf(
                '%s is not a valid number in base %d.',
                (string) $input,
                $radix
            ));
        }

        return NumberFactory::createNumber((string) $input, $radix);
    }

    /**
     * Checks if the given input only contains ASCII decimal digits.
     */
    private function isDecimal(USVStringInterface $input): bool
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
    private function isHexadecimal(USVStringInterface $input): bool
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
    private function isOctal(USVStringInterface $input): bool
    {
        foreach ($input as $codePoint) {
            if (!CodePoint::isAsciiOctalDigit($codePoint)) {
                return false;
            }
        }

        return true;
    }
}
