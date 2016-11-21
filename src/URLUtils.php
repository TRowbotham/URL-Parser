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
    const REGEX_ASCII_DOMAIN = '/[\x{0000}\x{0009}\x{000A}\x{000D}\x{0020}#%' .
        '\/:?@[\\\\\]]/';
    const REGEX_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}\x{0061}-\x{007A}]' .
        '[:|]/';
    const REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER = '/[\x{0041}-\x{005A}' .
        '\x{0061}-\x{007A}]:/';

    const ENCODE_SET_SIMPLE = 1;
    const ENCODE_SET_DEFAULT = 2;
    const ENCODE_SET_USERINFO = 3;

    public static $specialSchemes = [
        'ftp'    => 21,
        'file'   => '',
        'gopher' => 70,
        'http'   => 80,
        'https'  => 443,
        'ws'     => 80,
        'wss'    => 443
    ];

    /**
     * Converts a domain name to ASCII.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param string $aDomain The domain name to be converted.
     *
     * @return string|bool Returns the domain name upon success or false on
     *     failure.
     */
    public static function domainToASCII($aDomain)
    {
        // Let result be the result of running Unicode ToASCII with domain_name
        // set to domain, UseSTD3ASCIIRules set to false, processing_option set
        // to Transitional_Processing, and VerifyDnsLength set to false.
        $result = idn_to_ascii(
            $aDomain,
            0,
            INTL_IDNA_VARIANT_UTS46
        );

        if (!$result) {
            // Syntax violation
            return false;
        }

        return $result;
    }

    /**
     * Converts a domain name to Unicode.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param string $aDomain The domain name to be converted.
     *
     * @return string|bool Returns the domain name upon success or false on
     *     failure.
     */
    public static function domainToUnicode($aDomain)
    {
        // Let result be the result of running Unicode ToUnicode with
        // domain_name set to domain, UseSTD3ASCIIRules set to false.
        $result = idn_to_utf8(
            $aDomain,
            0,
            INTL_IDNA_VARIANT_UTS46
        );

        if (!$result) {
            // Syntax violation
            return false;
        }

        return $result;
    }

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
            } elseif ($aByteSequence[$i] === '%' &&
                !ctype_xdigit(substr($aByteSequence, $i + 1, 2))
            ) {
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
     * @param string $aEncoding Optional argument used to set the character
     *     encoding. Default is utf-8.
     *
     * @param bool $aUseCharset Optional argument that, if set to true,
     *     indicates if the charset specfied in the byte sequence should be used
     *     in place of the specified encoding argument. Default is null.
     *
     * @return string[]
     */
    public static function urlencodedParser(
        $aInput,
        $aEncodingOverride = null,
        $aUseCharset = null
    ) {
        $encoding = $aEncodingOverride ?: 'UTF-8';
        $len = strlen($aInput);

        if ($encoding !== 'UTF-8') {
            for ($i = 0; $i < $len; $i++) {
                // This can only happen if input was not generated through the
                // application/x-www-form-urlencoded serializer or through
                // URLSearchParams.
                if ($aInput[$i] > "\x7F") {
                    return false;
                }
            }
        }

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

            if ($aUseCharset && $name === '_charset_') {
                // TODO: Let result be the result of getting an encoding for
                // value, decoded. If result is not failure, unset use _charset_
                // flag and set encoding to result.
            }

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
            $outputPair = [];
            $outputPair['name'] = self::urlencodedByteSerializer(
                mb_convert_encoding($tuple['name'], $encoding)
            );

            if (isset($tuple['type']) && $tuple['type'] === 'hidden' &&
                $tuple['name'] === '_charset_'
            ) {
                $outputPair['value'] = $encoding;
            } elseif (isset($tuple['type']) && $tuple['type'] === 'file') {
                $outputPair['value'] = $tuple['value'];
            } else {
                $outputPair['value'] = self::urlencodedByteSerializer(
                    mb_convert_encoding($tuple['value'], $encoding)
                );
            }

            if ($key > 0) {
                $output .= '&';
            }

            $output .= $outputPair['name'] . '=' . $outputPair['value'];
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
        // The Simple Encode Set
        $inCodeSet = preg_match(self::REGEX_C0_CONTROLS, $aCodePoint) ||
            $aCodePoint > "\x7E";

        if (!$inCodeSet && $aEncodeSet >= self::ENCODE_SET_DEFAULT) {
            $inCodeSet = $inCodeSet || preg_match(
                '/[\x{0020}"#<>?`{}]/',
                $aCodePoint
            );
        }

        if (!$inCodeSet && $aEncodeSet == self::ENCODE_SET_USERINFO) {
            $inCodeSet = $inCodeSet || preg_match(
                '/[\/:;=@[\\\\\]^|]/',
                $aCodePoint
            );
        }

        if (!$inCodeSet) {
            return $aCodePoint;
        }

        $bytes = self::encode($aCodePoint);
        $result = '';

        for ($i = 0, $len = strlen($bytes); $i < $len; $i++) {
            $result .= rawurlencode($bytes[$i]);
        }

        return $result;
    }
}
