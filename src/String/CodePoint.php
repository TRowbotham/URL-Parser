<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use function rawurlencode;
use function strpbrk;

/**
 * A helper class for working with UTF-8 code points.
 *
 * @see https://infra.spec.whatwg.org/#code-points
 */
final class CodePoint
{
    public const C0_CONTROL_PERCENT_ENCODE_SET = 1;
    public const FRAGMENT_PERCENT_ENCODE_SET   = 2;
    public const PATH_PERCENT_ENCODE_SET       = 3;
    public const USERINFO_PERCENT_ENCODE_SET   = 4;

    public const ASCII_ALPHA_MASK = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    public const ASCII_ALNUM_MASK = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    public const ASCII_DIGIT_MASK = '0123456789';
    public const OCTAL_DIGIT_MASK = '01234567';
    public const HEX_DIGIT_MASK = 'ABCDEFabcdef0123456789';

    public const EOF = '';

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @see https://url.spec.whatwg.org/#url-code-points
     */
    public static function isUrlCodePoint(string $codePoint): bool
    {
        return (
                strpbrk($codePoint, self::ASCII_ALNUM_MASK) === $codePoint
                || $codePoint === '!'
                || $codePoint === '$'
                || ($codePoint >= '&' && $codePoint <= '/')
                || $codePoint === ':'
                || $codePoint === ';'
                || $codePoint === '='
                || $codePoint === '?'
                || $codePoint === '@'
                || $codePoint === '_'
                || $codePoint === '~'
                || ($codePoint >= "\xA0" && $codePoint <= "\u{10FFFD}")
            )

            // Not a surrogate
            && ($codePoint < "\u{D800}" || $codePoint > "\u{DFFF}")

            // Not a non-character
            && ($codePoint < "\u{FDD0}" || $codePoint > "\u{FDEF}")
            && $codePoint !== "\u{FFFE}"
            && $codePoint !== "\u{FFFF}"
            && $codePoint !== "\u{1FFFE}"
            && $codePoint !== "\u{1FFFF}"
            && $codePoint !== "\u{2FFFE}"
            && $codePoint !== "\u{2FFFF}"
            && $codePoint !== "\u{3FFFE}"
            && $codePoint !== "\u{3FFFF}"
            && $codePoint !== "\u{4FFFE}"
            && $codePoint !== "\u{4FFFF}"
            && $codePoint !== "\u{5FFFE}"
            && $codePoint !== "\u{5FFFF}"
            && $codePoint !== "\u{6FFFE}"
            && $codePoint !== "\u{6FFFF}"
            && $codePoint !== "\u{7FFFE}"
            && $codePoint !== "\u{7FFFF}"
            && $codePoint !== "\u{8FFFE}"
            && $codePoint !== "\u{8FFFF}"
            && $codePoint !== "\u{9FFFE}"
            && $codePoint !== "\u{9FFFF}"
            && $codePoint !== "\u{AFFFE}"
            && $codePoint !== "\u{AFFFF}"
            && $codePoint !== "\u{BFFFE}"
            && $codePoint !== "\u{BFFFF}"
            && $codePoint !== "\u{CFFFE}"
            && $codePoint !== "\u{CFFFF}"
            && $codePoint !== "\u{DFFFE}"
            && $codePoint !== "\u{DFFFF}"
            && $codePoint !== "\u{EFFFE}"
            && $codePoint !== "\u{EFFFF}"
            && $codePoint !== "\u{FFFFE}"
            && $codePoint !== "\u{FFFFF}"
            && $codePoint !== "\u{10FFFE}"
            && $codePoint !== "\u{10FFFF}";
    }

    /**
     * Encodes a code point if the code point is not part of the specified encode set.
     *
     * @see https://url.spec.whatwg.org/#utf-8-percent-encode
     *
     * @param string $codePoint        A code point to be encoded.
     * @param int    $percentEncodeSet The encode set used to decide whether or not the code point should be percent
     *                                 encoded.
     */
    public static function utf8PercentEncode(
        string $codePoint,
        int $percentEncodeSet = self::C0_CONTROL_PERCENT_ENCODE_SET
    ): string {
        $result = false;

        switch ($percentEncodeSet) {
            case self::USERINFO_PERCENT_ENCODE_SET:
                $result = $codePoint === '/'
                    || $codePoint === ':'
                    || $codePoint === ';'
                    || $codePoint === '='
                    || $codePoint === '@'
                    || $codePoint === '['
                    || $codePoint === '\\'
                    || $codePoint === ']'
                    || $codePoint === '^'
                    || $codePoint === '|';

                if ($result) {
                    break;
                }

                // No break.

            case self::PATH_PERCENT_ENCODE_SET:
                $result = $codePoint === '#'
                    || $codePoint === '?'
                    || $codePoint === '{'
                    || $codePoint === '}';

                if ($result) {
                    break;
                }

                // No break.

            case self::FRAGMENT_PERCENT_ENCODE_SET:
                $result = $codePoint === ' '
                    || $codePoint === '"'
                    || $codePoint === '<'
                    || $codePoint === '>'
                    || $codePoint === '`';

                if ($result) {
                    break;
                }

                // No break.

            case self::C0_CONTROL_PERCENT_ENCODE_SET:
                $result = ($codePoint >= "\0" && $codePoint <= "\x1F") || $codePoint >= "\x7E";

                break;
        }

        if (!$result) {
            return $codePoint;
        }

        return rawurlencode($codePoint);
    }
}
