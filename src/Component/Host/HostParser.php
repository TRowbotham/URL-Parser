<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\EncodeSet;
use Rowbot\URL\String\PercentEncoder;
use Rowbot\URL\String\USVStringInterface;

use function assert;
use function mb_strcut;
use function mb_strlen;
use function rawurldecode;

use const PREG_OFFSET_CAPTURE;

/**
 * @see https://url.spec.whatwg.org/#concept-host-parser
 */
class HostParser
{
    /**
     * @see https://url.spec.whatwg.org/#forbidden-host-code-point
     */
    public const FORBIDDEN_HOST_CODEPOINTS = '\x00\x09\x0A\x0D\x20#\/:<>?@[\\\\\]^|';

    /**
     * Parses a host string. The string could represent a domain, IPv4 or IPv6 address, or an opaque host.
     *
     * @param bool $isOpaque (optional) Whether or not the URL has a special scheme.
     *
     * @return \Rowbot\URL\Component\Host\HostInterface|false The returned Host can never be a null host.
     */
    public function parse(
        ParserContext $context,
        USVStringInterface $input,
        bool $isOpaque = false
    ): HostInterface|false {
        // 1. If input starts with U+005B ([), then:
        if ($input->startsWith('[')) {
            // 1.2. If input does not end with U+005D (]), IPv6-unclosed validation error, return failure.
            if (!$input->endsWith(']')) {
                // Validation error.
                $context->logger?->warning('IPv6-unclosed', [
                    'input'  => (string) $context->input,
                    'column' => $context->iter->key() + 2,
                ]);

                return false;
            }

            // 1.3. Return the result of IPv6 parsing input with its leading U+005B ([) and trailing U+005D (]) removed.
            return IPv6AddressParser::parse($context, $input->substr(1, -1));
        }

        // 2. If isOpaque is true, then return the result of opaque-host parsing input.
        if ($isOpaque) {
            return $this->parseOpaqueHost($context, $input);
        }

        // 3. Assert: input is not the empty string.
        assert(!$input->isEmpty());

        // 4. If input contains a percent-encoded byte, domain-percent-encoded validation error.
        foreach ($input as $i => $codePoint) {
            if ($codePoint === '%' && $input->substr($i + 1)->startsWithTwoAsciiHexDigits()) {
                $context->logger?->warning('domain-percent-encoded', [
                    'input'  => (string) $input,
                    'column_range' => [$i, $i + 2],
                ]);

                break;
            }
        }

        // 5. Let domain be the result of running UTF-8 decode without BOM on the percent-decoding of input.
        $domain = rawurldecode((string) $input);

        // 6. Let asciiDomain be the result of running domain parser with domain and false.
        $parser = new DomainParser();
        $asciiDomain = $parser->parse($context, $domain, false);

        // 7. If asciiDomain is failure, then return failure.
        if ($asciiDomain === false) {
            return false;
        }

        $asciiDomain = new StringHost($asciiDomain);

        // 8. If asciiDomain ends in a number:
        if (IPv4AddressParser::endsInIPv4Number($asciiDomain)) {
            return IPv4AddressParser::parse($context, $asciiDomain);
        }

        return $asciiDomain;
    }

    /**
     * Parses an opaque host.
     *
     * @see https://url.spec.whatwg.org/#concept-opaque-host-parser
     */
    private function parseOpaqueHost(ParserContext $context, USVStringInterface $input): HostInterface|false
    {
        $matches = [];

        if ($input->matches('/[' . self::FORBIDDEN_HOST_CODEPOINTS . ']/u', $matches, PREG_OFFSET_CAPTURE)) {
            // Validation error.
            $context->logger?->warning('host-invalid-code-point', [
                'input'  => (string) $input,
                'column' => mb_strlen(mb_strcut((string) $input, 0, $matches[0][1], 'utf-8'), 'utf-8') + 1,
            ]);

            return false;
        }

        foreach ($input as $i => $codePoint) {
            if ($codePoint !== '%' && !CodePoint::isUrlCodePoint($codePoint)) {
                // Validation error.
                $context->logger?->notice('invalid-URL-unit', [
                    'input'  => (string) $input,
                    'column' => $i,
                ]);
            } elseif ($codePoint === '%' && !$input->substr($i + 1)->startsWithTwoAsciiHexDigits()) {
                // Validation error.
                $context->logger?->notice('invalid-URL-unit', [
                    'input'  => (string) $input,
                    'column' => $i,
                ]);
            }
        }

        $percentEncoder = new PercentEncoder();
        $output = $percentEncoder->percentEncodeAfterEncoding('utf-8', (string) $input, EncodeSet::C0_CONTROL);

        return new StringHost($output);
    }
}
