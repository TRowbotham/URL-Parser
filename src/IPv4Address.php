<?php
namespace phpjs\urls;

class IPv4Address extends Host
{
    protected function __construct($aHost)
    {
        parent::__construct($aHost);
    }

    /**
     * Takes a string and parses it as an IPv4 address.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     *
     * @param string $aInput A string representing an IPv4 address.
     *
     * @return IPv4Address|string|bool Returns a IPv4Address object if the input
     *     is a valid IPv4 address or a string if the input is determined to be
     *     a domain. This will return false if the input is neither a domain or
     *     IPv4 address.
     */
    public static function parse($aInput)
    {
        $syntaxViolation = false;
        $parts = explode('.', $aInput);
        $len = count($parts);

        // If the last item in parts is an empty string, that is a syntax
        // violation, remove it from parts.
        if ($parts[$len - 1] === '') {
            $syntaxViolation = true;
            array_pop($parts);
            $len--;
        }

        // If there are more that 4 parts, this clearly isn't an IPv4 address
        // return the input given to us as this is probably a domain name.
        if ($len > 4) {
            return $aInput;
        }

        $numbers = [];

        foreach ($parts as $part) {
            // If any of the parts are an empty string, then this probably a
            // domain, so return the original input.
            if ($part === '') {
                return $aInput;
            }

            $n = self::parseIPv4Number($part, $syntaxViolation);

            // If the part is not a number, then this is probably a domain, so
            // return the original input.
            if ($n === false) {
                return $aInput;
            }

            $numbers[] = $n;
        }

        if ($syntaxViolation) {
            // Syntax violation
        }

        foreach ($numbers as $number) {
            if ($number > 255) {
                // Syntax violation
                break;
            }
        }

        $len = count($numbers);

        for ($i = 0; $i < $len - 1; $i++) {
            if ($numbers[$i] > 255) {
                return false;
            }
        }

        if ($numbers[$len - 1] >= pow(256, (5 - $len))) {
            // Syntax violation
            return false;
        }

        $ipv4 = gmp_init(array_pop($numbers), 10);
        $counter = 0;

        foreach ($numbers as $n) {
            $ipv4 += $n * gmp_pow(gmp_init(256, 10), (3 - $counter));
            $counter++;
        }

        return new static($ipv4);
    }

    /**
     * Serializes an IPv4 address in to a string.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-serializer
     *
     * @return string
     */
    public function serialize()
    {
        $output = '';
        $n = $this->mHost;

        for ($i = 0; $i < 4; $i++) {
            $output = intval($n % 256, 10) . $output;

            if ($i < 3) {
                $output = '.' . $output;
            }

            $n = floor($n / 256);
        }

        return $output;
    }

    /**
     * Takes a string and parses it as a valid IPv4 number.
     *
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @param string $aInput A string of numbers to be parsed.
     *
     * @param bool|null &$aSyntaxViolationFlag  A flag that represents if there
     *     was a syntax violation while parsing.
     *
     * @return int|bool Returns a bool on failure and an int otherwise.
     */
    protected static function parseIPv4Number($aInput, &$aSyntaxViolationFlag)
    {
        $input = $aInput;
        $R = 10;
        $len = strlen($input);

        if ($len > 1 &&
            (substr($input, 0, 2) === '0x' || substr($input, 0, 2) === '0X')
        ) {
            $aSyntaxViolationFlag = true;
            $input = substr($input, 2);
            $R = 16;
            $len -= 2;
        } elseif ($input === '') {
            return 0;
        } elseif ($len > 1 && $input[0] === '0') {
            $syntaxViolationFlag = true;
            $input = substr($input, 1);
            $R = 8;
        }

        if (($R == 10 && !ctype_digit($input)) ||
            ($R == 16 && !ctype_xdigit($input)) ||
            ($R == 8 && decoct(octdec($input)) != $input)) {
            return false;
        }

        // TODO: Return the mathematical integer value that is represented by
        // input in radix-R notation, using ASCII hex digits for digits with
        // values 0 through 15.
        return intval($input, $R);
    }
}
