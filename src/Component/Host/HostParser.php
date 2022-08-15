<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\Idna\Idna;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\EncodeSet;
use Rowbot\URL\String\PercentEncodeTrait;
use Rowbot\URL\String\USVStringInterface;

use function assert;
use function rawurldecode;

/**
 * @see https://url.spec.whatwg.org/#concept-host-parser
 */
class HostParser
{
    use PercentEncodeTrait;

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
    public function parse(
        ParserContext $context,
        USVStringInterface $input,
        bool $isNotSpecial = false
    ): HostInterface|false {
        if ($input->startsWith('[')) {
            if (!$input->endsWith(']')) {
                // Validation error.
                $context->logger?->warning('unclosed-ipv6-address');

                return false;
            }

            return IPv6AddressParser::parse($context, $input->substr(1, -1));
        }

        if ($isNotSpecial) {
            return $this->parseOpaqueHost($context, $input);
        }

        assert(!$input->isEmpty());
        $domain = rawurldecode((string) $input);
        $asciiDomain = $this->domainToAscii($context, $domain);

        if ($asciiDomain === false) {
            // Validation error.
            $context->logger?->warning('domain-to-ascii-failure');

            return false;
        }

        if ($asciiDomain->matches('/[' . self::FORBIDDEN_DOMAIN_CODEPOINTS . ']/u')) {
            // Validation error.
            $context->logger?->warning('domain-forbidden-code-point');

            return false;
        }

        if (IPv4AddressParser::endsInIPv4Number($asciiDomain)) {
            return IPv4AddressParser::parse($context, $asciiDomain);
        }

        return $asciiDomain;
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     */
    private function domainToAscii(ParserContext $context, string $domain, bool $beStrict = false): StringHost|false
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
            $context->logger?->warning('domain-to-ascii-empty-domain-failure');

            return false;
        }

        if ($result->hasErrors()) {
            // Validation error.
            $context->logger?->warning('domain-to-ascii-failure');

            return false;
        }

        return new StringHost($convertedDomain);
    }

    /**
     * Parses an opaque host.
     *
     * @see https://url.spec.whatwg.org/#concept-opaque-host-parser
     */
    private function parseOpaqueHost(ParserContext $context, USVStringInterface $input): HostInterface|false
    {
        if ($input->matches('/[' . self::FORBIDDEN_HOST_CODEPOINTS . ']/u')) {
            // Validation error.
            $context->logger?->warning('opaque-host-forbidden-code-point');

            return false;
        }

        foreach ($input as $i => $codePoint) {
            if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
                // Validation error.
                $context->logger?->notice('invalid-url-code-point');
            }

            if ($codePoint === '%' && !$input->substr($i + 1)->startsWithTwoAsciiHexDigits()) {
                // Validation error.
                $context->logger?->notice('unescaped-percent-sign');
            }
        }

        $output = $this->percentEncodeAfterEncoding('utf-8', (string) $input, EncodeSet::C0_CONTROL);

        return new StringHost($output);
    }
}
