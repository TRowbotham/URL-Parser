<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Exception\IDNATransformException;

use function idn_to_ascii;
use function intl_get_error_message;
use function sprintf;
use function strlen;

use const IDNA_CHECK_BIDI;
use const IDNA_CHECK_CONTEXTJ;
use const IDNA_ERROR_BIDI;
use const IDNA_ERROR_CONTEXTJ;
use const IDNA_ERROR_DISALLOWED;
use const IDNA_ERROR_DOMAIN_NAME_TOO_LONG;
use const IDNA_ERROR_EMPTY_LABEL;
use const IDNA_ERROR_HYPHEN_3_4;
use const IDNA_ERROR_INVALID_ACE_LABEL;
use const IDNA_ERROR_LABEL_HAS_DOT;
use const IDNA_ERROR_LABEL_TOO_LONG;
use const IDNA_ERROR_LEADING_COMBINING_MARK;
use const IDNA_ERROR_LEADING_HYPHEN;
use const IDNA_ERROR_PUNYCODE;
use const IDNA_ERROR_TRAILING_HYPHEN;
use const IDNA_NONTRANSITIONAL_TO_ASCII;
use const IDNA_USE_STD3_RULES;
use const INTL_IDNA_VARIANT_UTS46;

final class IDNA
{
    public const CHECK_HYPHENS              = 1;
    public const CHECK_BIDI                 = 2;
    public const CHECK_JOINERS              = 4;
    public const USE_STD3_ASCII_RULES       = 8;
    public const VERIFY_DNS_LENGTH          = 16;
    public const NONTRANSITIONAL_PROCESSING = 32;

    /**
     * @see https://secure.php.net/manual/en/intl.constants.php
     */
    private const IDNA_LENGTH_ERRORS = IDNA_ERROR_LABEL_TOO_LONG
        | IDNA_ERROR_DOMAIN_NAME_TOO_LONG
        | IDNA_ERROR_EMPTY_LABEL;
    private const IDNA_HYPHEN_ERRORS = IDNA_ERROR_HYPHEN_3_4
        | IDNA_ERROR_LEADING_HYPHEN
        | IDNA_ERROR_TRAILING_HYPHEN;
    private const IDNA_ERRORS = self::IDNA_HYPHEN_ERRORS
        | self::IDNA_LENGTH_ERRORS
        | IDNA_ERROR_LEADING_COMBINING_MARK
        | IDNA_ERROR_DISALLOWED
        | IDNA_ERROR_PUNYCODE
        | IDNA_ERROR_LABEL_HAS_DOT
        | IDNA_ERROR_INVALID_ACE_LABEL
        | IDNA_ERROR_BIDI
        | IDNA_ERROR_CONTEXTJ;

    private function __construct()
    {
    }

    private static function isFlagSet(int $flags, int $flag): bool
    {
        return ($flags & $flag) !== 0;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function processFlags(int $flags): array
    {
        $errors = self::IDNA_ERRORS;
        $options = 0;

        if (self::isFlagSet($flags, self::CHECK_BIDI)) {
            $options |= IDNA_CHECK_BIDI;
        }

        if (!self::isFlagSet($flags, self::CHECK_HYPHENS)) {
            $errors &= ~self::IDNA_HYPHEN_ERRORS;
        }

        if (self::isFlagSet($flags, self::CHECK_JOINERS)) {
            $options |= IDNA_CHECK_CONTEXTJ;
        }

        if (self::isFlagSet($flags, self::USE_STD3_ASCII_RULES)) {
            $options |= IDNA_USE_STD3_RULES;
        }

        return [$errors, $options];
    }

    public static function toAscii(string $domain, int $flags = 0): string
    {
        $verifyDnsLength = self::isFlagSet($flags, self::VERIFY_DNS_LENGTH);

        if (!$verifyDnsLength && $domain === '') {
            return '';
        }

        [$errors, $options] = self::processFlags($flags);

        if (!$verifyDnsLength) {
            $errors &= ~self::IDNA_LENGTH_ERRORS;
        }

        if (self::isFlagSet($flags, self::NONTRANSITIONAL_PROCESSING)) {
            $options |= IDNA_NONTRANSITIONAL_TO_ASCII;
        }

        idn_to_ascii($domain, $options, INTL_IDNA_VARIANT_UTS46, $info);

        // We died a horrible death and can't recover. Due to PHP Bug #72506, PHP's idn_to_*
        // functions fail to populate the $info array when the given domain exceeds 253 bytes.
        if ($info === []) {
            throw new IDNATransformException(sprintf(
                'Domain length exceeded the 253 byte limit; the given domain contained %d bytes.',
                strlen($domain)
            ));
        }

        if (($errors & $info['errors']) !== 0) {
            throw new IDNATransformException(intl_get_error_message());
        }

        return $info['result'];
    }
}
