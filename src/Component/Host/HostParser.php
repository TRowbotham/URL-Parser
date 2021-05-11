<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\Idna\Idna;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;

use function assert;
use function rawurldecode;

/**
 * @see https://url.spec.whatwg.org/#concept-host-parser
 */
class HostParser
{
    /**
     * @see https://url.spec.whatwg.org/#forbidden-host-code-point
     * @see https://url.spec.whatwg.org/#ref-for-forbidden-host-code-point%E2%91%A0
     */
    private const FORBIDDEN_OPAQUE_HOST_CODEPOINTS = '\x00\x09\x0A\x0D\x20#\/:<>?@[\\\\\]^|';
    private const FORBIDDEN_HOST_CODEPOINTS = self::FORBIDDEN_OPAQUE_HOST_CODEPOINTS . '%';

    /**
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @return \Rowbot\URL\Component\Host\StringHost|false
     */
    private static function domainToAscii(string $domain, bool $beStrict = false)
    {
        $result = Idna::toAscii($domain, [
            'CheckHyphens'            => false,
            'CheckBidi'               => true,
            'CheckJoiners'            => true,
            'UseSTD3ASCIIRules'       => $beStrict,
            'Transitional_Processing' => false,
            'VerifyDnsLength'         => $beStrict,
        ]);
        $convertedDomain = $result->getDomain();

        if ($convertedDomain === '') {
            // Validation error.
            return false;
        }

        if ($result->hasErrors()) {
            // Validation error.
            return false;
        }

        return new StringHost($convertedDomain);
    }

    /**
     * Parses a host string. The string could represent a domain, IPv4 or IPv6 address, or an opaque host.
     *
     * @param bool $isNotSpecial (optional) Whether or not the URL has a special scheme.
     *
     * @return \Rowbot\URL\Component\Host\HostInterface|false The returned Host can never be a null host.
     */
    public static function parse(USVStringInterface $input, bool $isNotSpecial = false)
    {
        if ($input->startsWith('[')) {
            if (!$input->endsWith(']')) {
                // Validation error.
                return false;
            }

            return IPv6AddressParser::parse($input->substr(1, -1));
        }

        if ($isNotSpecial) {
            return self::parseOpaqueHost($input);
        }

        assert(!$input->isEmpty());
        $domain = rawurldecode((string) $input);
        $asciiDomain = self::domainToAscii($domain);

        if ($asciiDomain === false) {
            // Validation error.
            return false;
        }

        $matches = $asciiDomain->matches('/[' . self::FORBIDDEN_HOST_CODEPOINTS . ']/u');

        if ($matches !== []) {
            // Validation error.
            return false;
        }

        $ipv4Host = IPv4AddressParser::parse($asciiDomain);

        if ($ipv4Host instanceof IPv4Address || $ipv4Host === false) {
            return $ipv4Host;
        }

        return $asciiDomain;
    }

    /**
     * Parses an opaque host.
     *
     * @see https://url.spec.whatwg.org/#concept-opaque-host-parser
     *
     * @return \Rowbot\URL\Component\Host\HostInterface|false
     */
    private static function parseOpaqueHost(USVStringInterface $input)
    {
        // Match a forbidden host code point, minus the "%" character.
        $matches = $input->matches('/[' . self::FORBIDDEN_OPAQUE_HOST_CODEPOINTS . ']/u');

        if ($matches !== []) {
            // Validation error.
            return false;
        }

        $output = '';

        foreach ($input as $i => $codePoint) {
            if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
                // Validation error.
            }

            if ($codePoint === '%' && !$input->substr($i + 1)->startsWithTwoAsciiHexDigits()) {
                // Validation error.
            }

            $output .= CodePoint::utf8PercentEncode($codePoint);
        }

        return new StringHost($output);
    }
}
