<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use GMP;
use Rowbot\URL\Component\Host\Exception\InvalidIPv4AddressException;
use Rowbot\URL\Component\Host\Exception\InvalidIPv4NumberException;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;

use function array_pop;
use function count;
use function gmp_strval;
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
            if ($number > 255) {
                // Validation error.
                break;
            }
        }

        $size = count($numbers);

        for ($i = 0; $i < $size - 1; ++$i) {
            if ($numbers[$i] > 255) {
                throw new InvalidIPv4NumberException(sprintf(
                    'Octet number %d had a value of %s, which exceeded the maximum valid size of 255.',
                    $i + 1,
                    gmp_strval($numbers[$i])
                ));
            }
        }

        if ($numbers[$size - 1] >= 256 ** (5 - $size)) {
            // Validation error.
            throw new InvalidIPv4NumberException(sprintf(
                'The last octet had a value of %d, which exceeded the maximum valid size of %s.',
                gmp_strval($numbers[$size - 1]),
                256 ** (5 - $size)
            ));
        }

        $ipv4 = array_pop($numbers);
        $counter = 0;

        foreach ($numbers as $number) {
            /** @var \GMP $ipv4 */
            $ipv4 += $number * 256 ** (3 - $counter);
            ++$counter;
        }

        return new IPv4Address($ipv4);
    }

    /**
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     */
    private function parseIPv4Number(USVStringInterface $input): GMP
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
            return gmp_init(0, 10);
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

        return gmp_init((string) $input, $radix);
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
