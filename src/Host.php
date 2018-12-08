<?php
declare(strict_types=1);

namespace Rowbot\URL;

use function is_string;
use function mb_substr;
use function preg_match;

class Host
{
    /**
     * @see https://url.spec.whatwg.org/#forbidden-host-code-point
     * @see https://url.spec.whatwg.org/#ref-for-forbidden-host-code-point%E2%91%A0
     */
    private const FORBIDDEN_CODEPOINTS  = '\x00\x09\x0A\x0D\x20#\/:?@[\\\\\]';
    private const FORBIDDEN_HOST        = '/[' . self::FORBIDDEN_CODEPOINTS . '%]/u';
    private const FORBIDDEN_OPAQUE_HOST = '/[' . self::FORBIDDEN_CODEPOINTS . ']/u';

    /**
     * @var \Rowbot\URL\NetworkAddress|string|null
     */
    private $host;

    /**
     * Constructor.
     *
     * @param \Rowbot\URL\NetworkAddress|string|null $host
     *
     * @return void
     */
    protected function __construct($host)
    {
        $this->host = $host;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        if ($this->host instanceof NetworkAddress) {
            $this->host = clone $this->host;
        }
    }

    /**
     * Creates a new Host whose host is null. This will serialize to the empty
     * string and is not a valid host string.
     *
     * @return self
     */
    public static function createNullHost(): self
    {
        return new self(null);
    }

    /**
     * Parses a host.
     *
     * @see https://url.spec.whatwg.org/#concept-host-parser
     *
     * @param string $input        An IPv4, IPv6 address, domain, or opaque host.
     *
     * @param bool   $isNotSpecial (optional) Whether or not the URL has a special scheme.
     *
     * @return self|false Returns a Host if it was successfully parsed or false if parsing fails. The returned Host can
     *                    never be null.
     */
    public static function parse(string $input, bool $isNotSpecial = false)
    {
        if (mb_substr($input, 0, 1, 'UTF-8') === '[') {
            if (mb_substr($input, -1, null, 'UTF-8') !== ']') {
                // Syntax violation
                return false;
            }

            $ipv6 = IPv6Address::parse(mb_substr($input, 1, -1, 'UTF-8'));

            if ($ipv6 === false) {
                return false;
            }

            return new self($ipv6);
        }

        if ($isNotSpecial) {
            return self::parseOpaqueHost($input);
        }

        // TODO: Let domain be the result of utf-8 decode without BOM on the
        // percent decoding of utf-8 encode on input
        $domain = URLUtils::percentDecode($input);
        $asciiDomain = self::domainToASCII($domain);

        if ($asciiDomain === false) {
            return false;
        }

        if (preg_match(self::FORBIDDEN_HOST, $asciiDomain) === 1) {
            // Syntax violation
            return false;
        }

        $ipv4Host = IPv4Address::parse($asciiDomain);

        if ($ipv4Host instanceof IPv4Address) {
            return new self($ipv4Host);
        }

        if ($ipv4Host === false) {
            return $ipv4Host;
        }

        return new self($asciiDomain);
    }

    /**
     * Parses an opaque host.
     *
     * @see https://url.spec.whatwg.org/#concept-opaque-host-parser
     *
     * @param string $input
     *
     * @return self|false
     */
    private static function parseOpaqueHost(string $input)
    {
        // Match a forbidden host code point, minus the "%" character.
        if (preg_match(self::FORBIDDEN_OPAQUE_HOST, $input) === 1) {
            return false;
        }

        $output = '';

        while (($char = mb_substr($input, 0, 1, 'UTF-8')) !== '') {
            $output .= URLUtils::utf8PercentEncode($char);
            $input = mb_substr($input, 1, null, 'UTF-8');
        }

        return new self($output);
    }

    /**
     * Determines if the host is an empty host.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->host === '';
    }

    /**
     * Returns whether or not the host has a null value.
     *
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->host === null;
    }

    /**
     * Checks to see if two hosts are equal.
     *
     * @param self|string $host Another Host object or a string.
     *
     * @return bool
     */
    public function equals($host): bool
    {
        if ($host instanceof self) {
            $host = $host->host;
        }

        if ($this->host instanceof NetworkAddress) {
            return $this->host->equals($host);
        }

        return $this->host === $host;
    }

    /**
     * Sets the host to a new value.
     *
     * @param \Rowbot\URL\NetworkAddress|string|null $host A new host value.
     *
     * @return void
     */
    public function setHost($host): void
    {
        if (!is_string($host)
            && !$host instanceof NetworkAddress
            && $host !== null
        ) {
            return;
        }

        $this->host = $host;
    }

    /**
     * Serializes a host.
     *
     * @see https://url.spec.whatwg.org/#concept-host-serializer
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->host instanceof IPv4Address) {
            return (string) $this->host;
        }

        if ($this->host instanceof IPv6Address) {
            return '[' . $this->host . ']';
        }

        // Since host can be null, make sure we cast this to a string.
        return (string) $this->host;
    }

    /**
     * Converts a domain name to ASCII.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param string $domain   The domain name to be converted.
     * @param bool   $beStrict
     *
     * @return string|false Returns the domain name upon success or false on failure.
     */
    private static function domainToASCII(string $domain, bool $beStrict = false)
    {
        $options = IDN::CHECK_BIDI
            | IDN::CHECK_JOINERS
            | IDN::NONTRANSITIONAL_PROCESSING;

        if ($beStrict) {
            $options |= IDN::USE_STD3_ASCII_RULES | IDN::VERIFY_DNS_LENGTH;
        }

        return IDN::getInstance()->toASCII($domain, $options);
    }
}
