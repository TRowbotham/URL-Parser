<?php
namespace phpjs\urls;

class IPv6Address extends Host
{
    protected function __construct($aHost)
    {
        parent::__construct($aHost);
    }

    /**
     * Parses an IPv6 string.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-parser
     *
     * @param string $aInput An IPv6 address.
     *
     * @return IPv6Address|bool Returns an IPv6Address if the string was
     *     successfully parsed as an IPv6 address or false if the input is not
     *     an IPv6 address.
     */
    public static function parse($aInput)
    {
        $address = [0, 0, 0, 0, 0, 0, 0, 0];
        $piecePointer = 0;
        $compressPointer = null;
        $pointer = 0;
        $c = mb_substr($aInput, $pointer, 1);

        if ($c === ':') {
            if (mb_substr($aInput, $pointer + 1, 1) !== ':') {
                // Syntax violation
                return false;
            }

            $pointer += 2;
            $piecePointer++;
            $compressPointer = $piecePointer;
            $c = mb_substr($aInput, $pointer, 1);
        }

        // Main
        while ($c !== '') {
            if ($piecePointer == 8) {
                // Syntax violation
                return false;
            }

            if ($c === ':') {
                if ($compressPointer !== null) {
                    // Syntax violation
                    return false;
                }

                $pointer++;
                $c = mb_substr($aInput, $pointer, 1);
                $piecePointer++;
                $compressPointer = $piecePointer;
                // Jump to main
                continue;
            }

            $value = 0;
            $length = 0;

            while ($length < 4 && ctype_xdigit($c)) {
                $value = $value * 0x10 + intval($c, 16);
                $pointer++;
                $length++;
                $c = mb_substr($aInput, $pointer, 1);
            }

            if ($c === '.') {
                if ($length == 0) {
                    // Syntax violation
                    return false;
                }

                $pointer -= $length;
                $c = mb_substr($aInput, $pointer, 1);

                goto IPv4;
            } elseif ($c === ':') {
                $pointer++;
                $c = mb_substr($aInput, $pointer, 1);

                if ($c === '') {
                    // Syntax violation
                    return false;
                }
            } elseif ($c !== '') {
                // Syntax violation
                return false;
            }

            $address[$piecePointer] = $value;
            $piecePointer++;
        }

        if ($c === '') {
            goto Finale;
        }

        // Step 8
        IPv4:
        if ($piecePointer > 6) {
            // Syntax violation
            return false;
        }

        // Step 9
        $dotsSeen = 0;

        // Step 10
        while ($c !== '') {
            $value = null;

            if (!ctype_digit($c)) {
                // Syntax violation
                return false;
            }

            while (ctype_digit($c)) {
                $number = base_convert(intval($c, 16), 16, 10);

                if ($value === null) {
                    $value = $number;
                } elseif ($value === 0) {
                    // Syntax violation
                } else {
                    $value = $value * 10 + $number;
                }

                $pointer++;
                $c = mb_substr($aInput, $pointer, 1);

                if ($value > 255) {
                    // Syntax violation
                    return false;
                }
            }

            if ($dotsSeen < 3 && $c !== '.') {
                // Syntax violation
                return false;
            }

            $address[$piecePointer] = $address[$piecePointer] * 0x100 + $value;

            if ($dotsSeen == 1 || $dotsSeen == 3) {
                $piecePointer++;
            }

            if ($c !== '') {
                $pointer++;
                $c = mb_substr($aInput, $pointer, 1);
            }

            if ($dotsSeen == 3 && $c !== '') {
                // Syntax violation
                return false;
            }

            $dotsSeen++;
        }

        Finale:
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
            // Syntax violation
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
    public function serialize()
    {
        $output = '';
        $compressPointer = null;
        $i = 0;
        $longestSequence = 1;

        // Finds the longest sequence, with a length greater than 1, of 16-bit
        // pieces that are 0 and sets the $compressPointer to the first 16-bit
        // piece in that sequence, otherwise $compressPointer will remain null.
        while ($i < 8) {
            if ($this->mHost[$i] == 0) {
                $sequenceLength = 0;

                while ($i < 8 && $this->mHost[$i] == 0) {
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

        foreach ($this->mHost as $index => $piece) {
            if ($compressPointer === $index) {
                $output .= $index == 0 ? '::' : ':';
            }

            // Ignore all subsequent 16-bit pieces that are 0 that fall within
            // the compressed range.
            if ($compressPointer !== null && $index >= $compressPointer &&
                $index < $compressPointer + $longestSequence
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
}
