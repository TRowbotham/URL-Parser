<?php
namespace Rowbot\URL;

class IPv6Address implements NetworkAddress
{
    /**
     * @var int[]
     */
    private $address;

    protected function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * Parses an IPv6 string.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-parser
     *
     * @param string $input An IPv6 address.
     *
     * @return \Rowbot\URL\IPv6Address|bool Returns an array if the string was successfully parsed as an IPv6 address or
     *                                      false if the input is not an IPv6 address.
     */
    public static function parse($input)
    {
        $address = [0, 0, 0, 0, 0, 0, 0, 0];
        $piecePointer = 0;
        $compressPointer = null;
        $pointer = 0;
        $c = mb_substr($input, $pointer, 1);

        if ($c === ':') {
            if (mb_substr($input, $pointer + 1, 1) !== ':') {
                // Validation error.
                return false;
            }

            $pointer += 2;
            $piecePointer++;
            $compressPointer = $piecePointer;
            $c = mb_substr($input, $pointer, 1);
        }

        while ($c !== '') {
            if ($piecePointer == 8) {
                // Validation error.
                return false;
            }

            if ($c === ':') {
                if ($compressPointer !== null) {
                    // Validation error.
                    return false;
                }

                $c = mb_substr($input, ++$pointer, 1);
                $piecePointer++;
                $compressPointer = $piecePointer;
                continue;
            }

            $value = 0;
            $length = 0;

            while ($length < 4 && ctype_xdigit($c)) {
                $value = $value * 0x10 + intval($c, 16);
                $length++;
                $c = mb_substr($input, ++$pointer, 1);
            }

            if ($c === '.') {
                if ($length == 0) {
                    // Validation error.
                    return false;
                }

                $pointer -= $length;
                $c = mb_substr($input, $pointer, 1);

                if ($piecePointer > 6) {
                    // Validation error.
                    return false;
                }

                $numbersSeen = 0;

                while ($c !== '') {
                    $ipv4Piece = null;

                    if ($numbersSeen > 0) {
                        if ($c === '.' && $numbersSeen < 4) {
                            $c = mb_substr($input, ++$pointer, 1);
                        } else {
                            // Validation error.
                            return false;
                        }
                    }

                    if (!ctype_digit($c)) {
                        // Validation error.
                        return false;
                    }

                    while (ctype_digit($c)) {
                        $number = (int) base_convert(intval($c, 16), 16, 10);

                        if ($ipv4Piece === null) {
                            $ipv4Piece = $number;
                        } elseif ($ipv4Piece === 0) {
                            // Validation error.
                            return false;
                        } else {
                            $ipv4Piece = $ipv4Piece * 10 + $number;
                        }

                        if ($ipv4Piece > 255) {
                            // Validation error.
                            return false;
                        }

                        $c = mb_substr($input, ++$pointer, 1);
                    }

                    $address[$piecePointer] = $address[
                        $piecePointer
                    ] * 0x100 + $ipv4Piece;
                    $numbersSeen++;

                    if ($numbersSeen == 2 || $numbersSeen == 4) {
                        $piecePointer++;
                    }
                }

                if ($numbersSeen != 4) {
                    // Validation error.
                    return false;
                }

                break;
            }

            if ($c === ':') {
                $c = mb_substr($input, ++$pointer, 1);

                if ($c === '') {
                    // Validation error.
                    return false;
                }
            } elseif ($c !== '') {
                // Validation error.
                return false;
            }

            $address[$piecePointer] = $value;
            $piecePointer++;
        }

        if ($compressPointer !== null) {
            $swaps = $piecePointer - $compressPointer;
            $piecePointer = 7;

            while ($piecePointer != 0 && $swaps > 0) {
                $temp = $address[$piecePointer];
                $address[$piecePointer] = $address[
                    $compressPointer + $swaps - 1
                ];
                $address[$compressPointer + $swaps - 1] = $temp;
                $piecePointer--;
                $swaps--;
            }
        } elseif ($compressPointer === null && $piecePointer != 8) {
            // Validation error.
            return false;
        }

        return new self($address);
    }

    /**
     * Serializes an IPv6 address.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-serializer
     *
     * @return string
     */
    public function __toString()
    {
        $output = '';
        $compressPointer = null;
        $i = 0;
        $longestSequence = 1;

        // Finds the longest sequence, with a length greater than 1, of 16-bit
        // pieces that are 0 and sets the $compressPointer to the first 16-bit
        // piece in that sequence, otherwise $compressPointer will remain null.
        while ($i < 8) {
            if ($this->address[$i] == 0) {
                $sequenceLength = 0;

                while ($i < 8 && $this->address[$i] == 0) {
                    $sequenceLength++;
                    $i++;
                }

                if ($sequenceLength > $longestSequence) {
                    $longestSequence = $sequenceLength;
                    $compressPointer = $i - $sequenceLength;
                }
            }

            $i++;
        }

        foreach ($this->address as $index => $piece) {
            if ($compressPointer === $index) {
                $output .= $index == 0 ? '::' : ':';
            }

            // Ignore all subsequent 16-bit pieces that are 0 that fall within
            // the compressed range.
            if ($compressPointer !== null && $index >= $compressPointer
                && $index < $compressPointer + $longestSequence
            ) {
                continue;
            }

            $output .= mb_strtolower(base_convert($piece, 10, 16));

            if ($index < 7) {
                $output .= ':';
            }
        }

        return $output;
    }

    /**
     * Checks to see if two IPv6 addresses are equal.
     *
     * @param  IPv6Address|string $address Another IPv6Address or a valid IPv6
     *                                     address string.
     *
     * @return bool
     */
    public function equals($address)
    {
        if ($address instanceof self) {
            return $this->address === $address->address;
        }

        if (is_string($address)) {
            $parsed = self::parse($address);

            return $parsed instanceof self
                && $this->address === $parsed->address;
        }

        return false;
    }
}
