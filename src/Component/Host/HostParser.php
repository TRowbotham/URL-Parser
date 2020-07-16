<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\Idna\Idna;
use Rowbot\URL\Component\Host\Exception\IDNATransformException;
use Rowbot\URL\Component\Host\Exception\InvalidHostException;
use Rowbot\URL\Component\Host\Exception\InvalidIPv4AddressException;
use Rowbot\URL\Component\Host\Exception\InvalidIPv4NumberException;
use Rowbot\URL\Component\Host\Exception\InvalidIPv6AddressException;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;

use function ord;
use function rawurldecode;
use function sprintf;

/**
 * @see https://url.spec.whatwg.org/#concept-host-parser
 */
class HostParser
{
    /**
     * @see https://url.spec.whatwg.org/#forbidden-host-code-point
     * @see https://url.spec.whatwg.org/#ref-for-forbidden-host-code-point%E2%91%A0
     */
    private const FORBIDDEN_OPAQUE_HOST_CODEPOINTS = '\x00\x09\x0A\x0D\x20#\/:<>?@[\\\\\]^';
    private const FORBIDDEN_HOST_CODEPOINTS = self::FORBIDDEN_OPAQUE_HOST_CODEPOINTS . '%';

    /**
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     */
    private function domainToAscii(string $domain, bool $beStrict = false): StringHost
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
            throw new IDNATransformException();
        }

        if ($result->hasErrors()) {
            // Validation error.
            throw new IDNATransformException();
        }

        return new StringHost($convertedDomain);
    }

    /**
     * Parses a host string. The string could represent a domain, IPv4 or IPv6 address, or an opaque host.
     *
     * @param bool $isNotSpecial (optional) Whether or not the URL has a special scheme.
     *
     * @return \Rowbot\URL\Component\Host\HostInterface The returned Host can never be a null host.
     */
    public function parse(USVStringInterface $input, bool $isNotSpecial = false): HostInterface
    {
        if ($input->startsWith('[')) {
            if (!$input->endsWith(']')) {
                // Validation error.
                throw new InvalidIPv6AddressException(sprintf(
                    'IPv6 address must end with "]", but found "%s".',
                    (string) $input->substr(-1)
                ));
            }

            $ipv6 = new IPv6AddressParser();

            return $ipv6->parse($input->substr(1, -1));
        }

        if ($isNotSpecial) {
            return $this->parseOpaqueHost($input);
        }

        if ($input->isEmpty()) {
            throw new InvalidHostException('The domain must not be an empty string.');
        }

        $domain = rawurldecode((string) $input);

        try {
            $asciiDomain = $this->domainToAscii($domain);
        } catch (IDNATransformException $e) {
            // Validation error.
            throw $e;
        }

        $matches = $asciiDomain->matches('/[' . self::FORBIDDEN_HOST_CODEPOINTS . ']/u');

        if ($matches !== []) {
            throw new InvalidHostException(sprintf(
                'The domain contained the forbidden code point U+%04X "%s".',
                ord($matches[0]),
                $matches[0]
            ));
        }

        $ipv4Host = new IPv4AddressParser();

        try {
            return $ipv4Host->parse($asciiDomain);
        } catch (InvalidIPv4AddressException $e) {
            return $asciiDomain;
        } catch (InvalidIPv4NumberException $e) {
            // Validation error.
            throw $e;
        }
    }

    /**
     * Parses an opaque host.
     *
     * @see https://url.spec.whatwg.org/#concept-opaque-host-parser
     *
     * @return \Rowbot\URL\Component\Host\HostInterface
     */
    private function parseOpaqueHost(USVStringInterface $input): HostInterface
    {
        // Match a forbidden host code point, minus the "%" character.
        $matches = $input->matches('/[' . self::FORBIDDEN_OPAQUE_HOST_CODEPOINTS . ']/u');

        if ($matches !== []) {
            throw new InvalidHostException(sprintf(
                'The domain contained the forbidden code point U+%04X "%s".',
                ord($matches[0]),
                $matches[0]
            ));
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
