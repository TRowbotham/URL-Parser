<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;

use function intval;
use function strpbrk;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv6-parser
 */
class IPv6AddressParser
{
    /**
     * @return \Rowbot\URL\Component\Host\IPv6Address|false
     */
    public static function parse(USVStringInterface $input)
    {
        $address = [0, 0, 0, 0, 0, 0, 0, 0];
        $pieceIndex = 0;
        $compress = null;
        $iter = $input->getIterator();
        $iter->rewind();

        if ($iter->current() === ':') {
            if ($iter->peek() !== ':') {
                // Validation error.
                return false;
            }

            $iter->seek(2);
            $compress = ++$pieceIndex;
        }

        while ($iter->valid()) {
            if ($pieceIndex === 8) {
                // Validation error.
                return false;
            }

            if ($iter->current() === ':') {
                if ($compress !== null) {
                    // Validation error.
                    return false;
                }

                $iter->next();
                $compress = ++$pieceIndex;

                continue;
            }

            $value = 0;
            $length = 0;
            $current = $iter->current();

            while ($length < 4 && strpbrk($current, CodePoint::HEX_DIGIT_MASK) === $current) {
                $value = ($value * 0x10) + intval($current, 16);
                $iter->next();
                ++$length;
                $current = $iter->current();
            }

            if ($iter->current() === '.') {
                if ($length === 0) {
                    // Validation error.
                    return false;
                }

                $iter->seek(-$length);

                if ($pieceIndex > 6) {
                    // Validation error.
                    return false;
                }

                $result = self::parseIPv4Address($iter, $address, $pieceIndex);

                if ($result === false) {
                    return false;
                }

                [$address, $pieceIndex] = $result;

                break;
            }

            if ($iter->current() === ':') {
                $iter->next();

                if (!$iter->valid()) {
                    // Validation error.
                    return false;
                }
            } elseif ($iter->valid()) {
                // Validation error.
                return false;
            }

            $address[$pieceIndex++] = $value;
        }

        if ($compress !== null) {
            $swaps = $pieceIndex - $compress;
            $pieceIndex = 7;

            while ($pieceIndex !== 0 && $swaps > 0) {
                $temp = $address[$pieceIndex];
                $address[$pieceIndex] = $address[$compress + $swaps - 1];
                $address[$compress + $swaps - 1] = $temp;
                --$pieceIndex;
                --$swaps;
            }
        } elseif ($pieceIndex !== 8) {
            // Validation error.
            return false;
        }

        return new IPv6Address($address);
    }

    /**
     * @param list<int> $address
     *
     * @return array{0: list<int>, 1: int}|false
     */
    private static function parseIPv4Address(
        StringIteratorInterface $iter,
        array $address,
        int $pieceIndex
    ) {
        $numbersSeen = 0;

        do {
            $ipv4Piece = null;

            if ($numbersSeen > 0) {
                if ($iter->current() !== '.' && $numbersSeen >= 4) {
                    // Validation error.
                    return false;
                }

                $iter->next();
            }

            $current = $iter->current();

            if (strpbrk($current, CodePoint::ASCII_DIGIT_MASK) !== $current) {
                // Validation error.
                return false;
            }

            do {
                $number = (int) $current;

                if ($ipv4Piece === null) {
                    $ipv4Piece = $number;
                } elseif ($ipv4Piece === 0) {
                    // Validation error.
                    return false;
                } else {
                    $ipv4Piece = ($ipv4Piece * 10) + $number;
                }

                if ($ipv4Piece > 255) {
                    // Validation error.
                    return false;
                }

                $iter->next();
                $current = $iter->current();
            } while (strpbrk($current, CodePoint::ASCII_DIGIT_MASK) === $current);

            $piece = $address[$pieceIndex];
            $address[$pieceIndex] = ($piece * 0x100) + $ipv4Piece;
            ++$numbersSeen;

            if ($numbersSeen === 2 || $numbersSeen === 4) {
                ++$pieceIndex;
            }
        } while ($iter->valid());

        if ($numbersSeen !== 4) {
            // Validation error.
            return false;
        }

        return [$address, $pieceIndex];
    }
}
