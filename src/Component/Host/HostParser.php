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
     * @see https://url.spec.whatwg.org/#forbidden-domain-code-point
     */
    private const FORBIDDEN_HOST_CODEPOINTS = '\x00\x09\x0A\x0D\x20#\/:<>?@[\\\\\]^|';
    private const FORBIDDEN_DOMAIN_CODEPOINTS = self::FORBIDDEN_HOST_CODEPOINTS . '\x01-\x1F%\x7F';

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

        if ($asciiDomain->matches('/[' . self::FORBIDDEN_DOMAIN_CODEPOINTS . ']/u')) {
            // Validation error.
            return false;
        }

        if (IPv4AddressParser::endsInIPv4Number($asciiDomain)) {
            return IPv4AddressParser::parse($asciiDomain);
        }

        return $asciiDomain;
    }

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
     * Parses an opaque host.
     *
     * @see https://url.spec.whatwg.org/#concept-opaque-host-parser
     *
     * @return \Rowbot\URL\Component\Host\HostInterface|false
     */
    private static function parseOpaqueHost(USVStringInterface $input)
    {
        if ($input->matches('/[' . self::FORBIDDEN_HOST_CODEPOINTS . ']/u')) {
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
