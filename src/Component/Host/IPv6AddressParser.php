<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Exception\InvalidIPv6AddressException;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;

use function intval;
use function sprintf;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv6-parser
 */
class IPv6AddressParser
{
    /**
     * @var array<int, int>
     */
    private $address;

    /**
     * @var int
     */
    private $pieceIndex;

    public function __construct()
    {
        $this->address = [];
        $this->pieceIndex = 0;
    }

    public function parse(USVStringInterface $input): IPv6Address
    {
        $this->address = [0, 0, 0, 0, 0, 0, 0, 0];
        $this->pieceIndex = 0;
        $compress = null;
        $iter = $input->getIterator();
        $iter->rewind();

        if ($iter->current() === ':') {
            if ($iter->peek() !== ':') {
                // Validation error.
                throw new InvalidIPv6AddressException(sprintf(
                    'Invalid compressed IPv6 address. Expected ":", but found "%s".',
                    $iter->current()
                ));
            }

            $iter->seek(2);
            $compress = ++$this->pieceIndex;
        }

        while ($iter->valid()) {
            if ($this->pieceIndex === 8) {
                // Validation error.
                throw new InvalidIPv6AddressException('IPv6 address contained more than 8 pieces.');
            }

            if ($iter->current() === ':') {
                if ($compress !== null) {
                    // Validation error.
                    throw new InvalidIPv6AddressException(
                        'IPv6 address contained multiple compressions.'
                    );
                }

                $iter->next();
                $compress = ++$this->pieceIndex;

                continue;
            }

            $value = 0;
            $length = 0;

            while ($length < 4 && CodePoint::isAsciiHexDigit($iter->current())) {
                $value = ($value * 0x10) + intval($iter->current(), 16);
                $iter->next();
                ++$length;
            }

            if ($iter->current() === '.') {
                if ($length === 0) {
                    // Validation error.
                    throw new InvalidIPv6AddressException(
                        'IPv4 address in IPv6 address contained an empty octet.'
                    );
                }

                $iter->seek(-$length);

                if ($this->pieceIndex > 6) {
                    // Validation error.
                    throw new InvalidIPv6AddressException(
                        'IPv6 address must not have more than 6 pieces when it also contains an IPv4 address.'
                    );
                }

                $this->parseIPv4Address($iter);

                break;
            }

            if ($iter->current() === ':') {
                $iter->next();

                if (!$iter->valid()) {
                    // Validation error.
                    throw new InvalidIPv6AddressException(
                        'IPv6 address ended prematurely. Expected to find a number, but found nothing.'
                    );
                }
            } elseif ($iter->valid()) {
                // Validation error.
                throw new InvalidIPv6AddressException(sprintf(
                    'Invalid IPv6 delimiter. Expected ":", but found "%s".',
                    $iter->current()
                ));
            }

            $this->address[$this->pieceIndex++] = $value;
        }

        if ($compress !== null) {
            $swaps = $this->pieceIndex - $compress;
            $this->pieceIndex = 7;

            while ($this->pieceIndex !== 0 && $swaps > 0) {
                $temp = $this->address[$this->pieceIndex];
                $this->address[$this->pieceIndex] = $this->address[$compress + $swaps - 1];
                $this->address[$compress + $swaps - 1] = $temp;
                --$this->pieceIndex;
                --$swaps;
            }
        } elseif ($this->pieceIndex !== 8) {
            // Validation error.
            throw new InvalidIPv6AddressException(sprintf(
                'A non-compressed IPv6 address must contain 8 pieces, but the address only contained %d pieces.',
                $this->pieceIndex
            ));
        }

        return new IPv6Address($this->address);
    }

    private function parseIPv4Address(StringIteratorInterface $iter): void
    {
        $numbersSeen = 0;

        do {
            $ipv4Piece = null;

            if ($numbersSeen > 0) {
                if ($iter->current() !== '.' && $numbersSeen >= 4) {
                    // Validation error.
                    throw new InvalidIPv6AddressException(sprintf(
                        'IPv4 address must contain 4 octets, but found %d.',
                        $numbersSeen
                    ));
                }

                $iter->next();
            }

            if (!CodePoint::isAsciiDigit($iter->current())) {
                // Validation error.
                throw new InvalidIPv6AddressException(sprintf(
                    'IPv4 address must only contain ASCII digits, but found "%s" instead.',
                    $iter->current()
                ));
            }

            do {
                $number = (int) $iter->current();

                if ($ipv4Piece === null) {
                    $ipv4Piece = $number;
                } elseif ($ipv4Piece === 0) {
                    // Validation error.
                    throw new InvalidIPv6AddressException('The first IPv4 octet must not be 0.');
                } else {
                    $ipv4Piece = ($ipv4Piece * 10) + $number;
                }

                if ($ipv4Piece > 255) {
                    // Validation error.
                    throw new InvalidIPv6AddressException(sprintf(
                        'IPv4 octets cannot be greater than 255, %d given.',
                        $ipv4Piece
                    ));
                }

                $iter->next();
            } while (CodePoint::isAsciiDigit($iter->current()));

            $piece = $this->address[$this->pieceIndex];
            $this->address[$this->pieceIndex] = ($piece * 0x100) + $ipv4Piece;
            ++$numbersSeen;

            if ($numbersSeen === 2 || $numbersSeen === 4) {
                ++$this->pieceIndex;
            }
        } while ($iter->valid());

        if ($numbersSeen !== 4) {
            // Validation error.
            throw new InvalidIPv6AddressException(sprintf(
                'IPv4 address must contain 4 octets, but found %d.',
                $numbersSeen
            ));
        }
    }
}
