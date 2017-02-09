<?php
namespace phpjs\urls;

abstract class URLUtils
{
    const REGEX_C0_CONTROLS = '/[\x{0000}-\x{001F}]/';
    const REGEX_ASCII_ALPHA = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]/';
    const REGEX_ASCII_ALPHANUMERIC = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}' .
        '\x{0061}-\x{007A}]/';
    const REGEX_URL_CODE_POINTS = '/[\x{0030}-\x{0039}\x{0041}-\x{005A}' .
        '\x{0061}-\x{007A}' .
        '!$&\'()*+,\-.\/:;=?@_~' .
        '\x{00A0}-\x{D7DD}' .
        '\x{E000}-\x{FDCF}' .
        '\x{FDF0}-\x{FFFD}' .
        '\x{10000}-\x{1FFFD}' .
        '\x{20000}-\x{2FFFD}' .
        '\x{30000}-\x{3FFFD}' .
        '\x{40000}-\x{4FFFD}' .
        '\x{50000}-\x{5FFFD}' .
        '\x{60000}-\x{6FFFD}' .
        '\x{70000}-\x{7FFFD}' .
        '\x{80000}-\x{8FFFD}' .
        '\x{90000}-\x{9FFFD}' .
        '\x{A0000}-\x{AFFFD}' .
        '\x{B0000}-\x{BFFFD}' .
        '\x{C0000}-\x{CFFFD}' .
        '\x{D0000}-\x{DFFFD}' .
        '\x{E0000}-\x{EFFFD}' .
        '\x{F0000}-\x{FFFFD}' .
        '\x{100000}-\x{10FFFD}' .
         ']/u';
    const REGEX_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]' .
        '[:|]/';
    const REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER = '/^[\x{0041}-\x{005A}' .
        '\x{0061}-\x{007A}]:$/u';

    const ENCODE_SET_SIMPLE   = '\x00-\x1F\x7E-\x{10FFFF}';
    const ENCODE_SET_DEFAULT  = self::ENCODE_SET_SIMPLE . '\x20"#<>?`{}';
    const ENCODE_SET_USERINFO = self::ENCODE_SET_DEFAULT . '\/:;=@[\\\\\]^|';

    public static $specialSchemes = [
        'ftp'    => 21,
        'file'   => '',
        'gopher' => 70,
        'http'   => 80,
        'https'  => 443,
        'ws'     => 80,
        'wss'    => 443
    ];

    public static function encode($aStream, $aEncoding = 'UTF-8')
    {
        $inputEncoding = mb_detect_encoding($aStream);

        return mb_convert_encoding($aStream, $aEncoding, $inputEncoding);
    }

    /**
     * Decodes a percent encoded byte into a code point.
     *
     * @see https://url.spec.whatwg.org/#percent-decode
     *
     * @param  string $aByteSequence A byte sequence to be decoded.
     *
     * @return string
     */
    public static function percentDecode($aByteSequence)
    {
        $output = '';

        for ($i = 0, $len = strlen($aByteSequence); $i < $len; $i++) {
            if ($aByteSequence[$i] !== '%') {
                $output .= $aByteSequence[$i];
            } elseif (!preg_match(
                '/%[A-Fa-f0-9]{2}/',
                substr($aByteSequence, $i, 3)
            )) {
                $output .= $aByteSequence[$i];
            } else {
                // TODO: utf-8 decode without BOM
                $bytePoint = pack('H*', substr($aByteSequence, $i + 1, 2));
                $output .= $bytePoint;
                $i += 2;
            }
        }

        return $output;
    }

    /**
     * Serializes the individual bytes of the given byte sequence to be
     * compatible with application/x-www-form-encoded URLs.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-byte-serializer
     *
     * @param  string $aInput A byte sequence to be serialized.
     *
     * @return string
     */
    public static function urlencodedByteSerializer($aInput)
    {
        $output = '';

        for ($i = 0, $len = strlen($aInput); $i < $len; $i++) {
            if ($aInput[$i] == "\x20") {
                $output .= "\x2B";
            } elseif ($aInput[$i] === "\x2A" ||
                $aInput[$i] === "\x2D" ||
                $aInput[$i] === "\x2E" ||
                ($aInput[$i] >= "\x30" && $aInput[$i] <= "\x39") ||
                ($aInput[$i] >= "\x41" && $aInput[$i] <= "\x5A") ||
                $aInput[$i] === "\x5F" ||
                ($aInput[$i] >= "\x61" && $aInput[$i] <= "\x7A")
            ) {
                $output .= $aInput[$i];
            } else {
                $output .= rawurlencode($aInput[$i]);
            }
        }

        return $output;
    }

    /**
     * Encodes a byte sequence to be compatible with the
     * application/x-www-form-urlencoded encoding.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-parser
     *
     * @param string $aInput A byte sequence to be encoded.
     *
     * @return string[][]
     */
    public static function urlencodedParser($aInput)
    {
        $sequences = explode('&', $aInput);
        $tuples = [];

        foreach ($sequences as $bytes) {
            if ($bytes === '') {
                continue;
            }

            $pos = strpos($bytes, '=');

            if ($pos !== false) {
                $name = substr($bytes, 0, $pos);
                $value = substr($bytes, $pos + 1);
            } else {
                $name = $bytes;
                $value = '';
            }

            $name = str_replace('+', "\x20", $name);
            $value = str_replace('+', "\x20", $value);

            $tuples[] = [
                'name' => $name,
                'value' => $value
            ];
        }

        $output = [];

        foreach ($tuples as $tuple) {
            // TODO: For each name-value tuple in tuples, append a new
            // name-value tuple to output where the new name and value appended
            // to output are the result of running decode on the percent
            // decoding of the name and value from tuples, respectively, using
            // encoding.
            $output[] = [
                'name' => self::percentDecode($tuple['name']),
                'value' => self::percentDecode($tuple['value'])
            ];
        }

        return $output;
    }

    /**
     * Takes a list of name-value or name-value-type tuples and serializes them.
     * The HTML standard invokes this algorithm with name-value-type tuples.
     * This is primarily used for data that needs to be
     * application/x-www-form-urlencoded.
     *
     * @see https://url.spec.whatwg.org/#concept-urlencoded-serializer
     *
     * @param string[] $aTuples A list of name-value-type tuples to be
     *     serialized.
     *
     * @param string|null $aEncodingOverride The encoding to use for the data.
     *     By default, it will use UTF-8.
     *
     * @return string
     */
    public static function urlencodedSerializer(
        array $aTuples,
        $aEncodingOverride = null
    ) {
        // TODO: If encoding override is given, set encoding to the result of
        // getting an output encoding from encoding override.
        $encoding = $aEncodingOverride ?: 'UTF-8';
        $output = '';

        foreach ($aTuples as $key => $tuple) {
            $name = self::urlencodedByteSerializer(
                mb_convert_encoding($tuple['name'], $encoding)
            );
            $value = $tuple['value'];

            if (isset($tuple['type'])) {
                if ($tuple['type'] === 'hidden' && $name === '_charset_') {
                    $value = $encoding;
                } elseif ($tuple['type'] === 'file') {
                    // TODO: set $value to value's filename.
                }
            }

            $value = self::urlencodedByteSerializer(
                mb_convert_encoding($value, $encoding)
            );

            if ($key > 0) {
                $output .= '&';
            }

            $output .= $name . '=' . $value;
        }

        return $output;
    }

    public static function urlencodedStringParser($aInput)
    {
        return self::urlencodedParser(self::encode($aInput));
    }

    /**
     * Encodes a code point stream if the code point is not part of the
     * specified encode set.
     *
     * @see https://url.spec.whatwg.org/#utf-8-percent-encode
     *
     * @param string $aCodePoint A code point stream to be encoded.
     *
     * @param int $aEncodeSet The encode set used to decide whether or not the
     *     code point should be encoded.
     *
     * @return string
     */
    public static function utf8PercentEncode(
        $aCodePoint,
        $aEncodeSet = self::ENCODE_SET_SIMPLE
    ) {
        if (!preg_match('/[' . $aEncodeSet . ']/u', $aCodePoint)) {
            return $aCodePoint;
        }

        return rawurlencode($aCodePoint);
    }

    /**
     * This is mostly designed to keep tests happy, however, I'm not so sure
     * its the right thing to do here. This makes string conversions work more
     * like they do in JavaScript, which differs from the default conversions in
     * PHP, which can be unexpected.
     *
     * @param  mixed $arg A value to cast to a string.
     *
     * @return string
     */
    public static function strval($arg)
    {
        if (is_string($arg)) {
            return $arg;
        }

        if (is_bool($arg)) {
            return $arg ? 'true' : 'false';
        }

        if ($arg === null) {
            return 'null';
        }

        if (is_scalar($arg)) {
            return (string) $arg;
        }

        if (is_object($arg)) {
            if (method_exists($arg, '__toString')) {
                return (string) $arg;
            }

            return get_class($arg);
        }

        return '';
    }
}
