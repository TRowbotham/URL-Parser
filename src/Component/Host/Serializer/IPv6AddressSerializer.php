<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Serializer;

use function dechex;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv6-serializer
 */
class IPv6AddressSerializer implements HostSerializerInterface
{
    private const MAX_SIZE = 8;

    /**
     * @var array<int, int>
     */
    private $address;

    /**
     * @param array<int, int> $address
     */
    public function __construct(array $address)
    {
        $this->address = $address;
    }

    /**
     * Finds the longest sequence, with a length greater than 1, of 16-bit pieces that are 0 and
     * sets $compress to the first 16-bit piece in that sequence, otherwise $compress will remain
     * null.
     *
     * @return array{0: int|null, 1: int} The first item is the compress pointer, which indicates where in the address
     *                                    it can start compression, or null if the address isn't compressable. The
     *                                    second item is the length of the longest sequence of zeroes.
     */
    private function getCompressLocation(): array
    {
        $longestSequence = 1;
        $compress = null;
        $i = 0;

        do {
            if ($this->address[$i] !== 0) {
                continue;
            }

            $sequenceLength = 0;

            do {
                ++$sequenceLength;
                ++$i;
            } while ($i < self::MAX_SIZE && $this->address[$i] === 0);

            // We are only interested in sequences with a length greater than one. We also only want
            // to note the first of those sequences since there may be multiple sequences of zero
            // that have the same length.
            if ($sequenceLength > $longestSequence) {
                $longestSequence = $sequenceLength;
                $compress = $i - $sequenceLength;
            }
        } while (++$i < self::MAX_SIZE);

        return [$compress, $longestSequence];
    }

    public function toFormattedString(): string
    {
        return '[' . $this->toString() . ']';
    }

    public function toString(): string
    {
        $output = '';
        [$compress, $longestSequence] = $this->getCompressLocation();
        $pieceIndex = 0;

        do {
            if ($compress === $pieceIndex) {
                $output .= $pieceIndex === 0 ? '::' : ':';

                // Advance the pointer to $compress + $longestSequence
                // to skip over all 16-bit pieces that are 0 that immediately
                // follow the piece at $compress.
                $pieceIndex = $compress + $longestSequence;

                continue;
            }

            // Is it safe to assume this always returns lowercase letters?
            $output .= dechex($this->address[$pieceIndex]);

            if ($pieceIndex < self::MAX_SIZE - 1) {
                $output .= ':';
            }

            ++$pieceIndex;
        } while ($pieceIndex < self::MAX_SIZE);

        return $output;
    }
}
