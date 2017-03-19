<?php
namespace phpjs\urls;

class Host
{
    const FORBIDDEN_HOST_CODEPOINT = '/[\x00\x09\x0A\x0D\x20#%\/:?@[\\\\\]]/u';

    private $host;

    protected function __construct($host)
    {
        $this->host = $host;
    }

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
     * @return Host
     */
    public static function createNullHost()
    {
        return new self(null);
    }

    /**
     * Parses a host.
     *
     * @see https://url.spec.whatwg.org/#concept-host-parser
     *
     * @param  string $input     An IPv4, IPv6 address, domain, or opaque host.
     *
     * @param  bool   $isSpecial Whether or not the URL has a special scheme.
     *
     * @return Host|bool Returns a Host if it was successfully parsed or
     *                   false if parsing fails. The returned Host can never be
     *                   null.
     */
    public static function parse($input, $isSpecial)
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

        if (!$isSpecial) {
            return self::parseOpaqueHost($input);
        }

        // TODO: Let domain be the result of utf-8 decode without BOM on the
        // percent decoding of utf-8 encode on input
        $domain = URLUtils::percentDecode(URLUtils::encode($input));
        $asciiDomain = self::domainToASCII($domain);

        if ($asciiDomain === false) {
            return false;
        }

        if (preg_match(self::FORBIDDEN_HOST_CODEPOINT, $asciiDomain)) {
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
     * @param  string $input
     *
     * @return Host|bool
     */
    private static function parseOpaqueHost($input)
    {
        // Match a forbidden host code point, minus the "%" character.
        if (preg_match(self::FORBIDDEN_HOST_CODEPOINT, $input, $matches) &&
            $matches[0] !== '%'
        ) {
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
     * Returns whether or not the host has a null value.
     *
     * @return bool
     */
    public function isNull()
    {
        return $this->host === null;
    }

    /**
     * Returns whether or not the host is a domain.
     *
     * @return bool
     */
    public function isDomain()
    {
        return $this->isValidDomain();
    }

    /**
     * Returns whether or not the host is a valid domain.
     *
     * @see https://url.spec.whatwg.org/#valid-domain
     *
     * @return bool
     */
    private function isValidDomain()
    {
        if (!is_string($this->host)) {
            return false;
        }

        // Let result be the result of running Unicode ToASCII with domain_name
        // set to domain, UseSTD3ASCIIRules set to true, processing_option set
        // to Nontransitional_Processing, and VerifyDnsLength set to true.
        $result = idn_to_ascii(
            $this->host,
            IDNA_USE_STD3_RULES | IDNA_NONTRANSITIONAL_TO_ASCII,
            INTL_IDNA_VARIANT_UTS46
        );

        if ($result === false) {
            return false;
        }

        // Set result to the result of running Unicode ToUnicode with
        // domain_name set to result, UseSTD3ASCIIRules set to true.
        $result = idn_to_utf8(
            $result,
            IDNA_USE_STD3_RULES,
            INTL_IDNA_VARIANT_UTS46,
            $info
        );

        if ($result === false ||
            (!empty($info) && $info['errors'] !== 0)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks to see if two hosts are equal.
     *
     * @param  Host|string $host Another Host object or a string.
     *
     * @return bool
     */
    public function equals($host)
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
     * @param  string|NetworkAddress|null A new host value.
     */
    public function setHost($host)
    {
        if (!is_string($host) &&
            !$host instanceof NetworkAddress &&
            $host !== null
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
    public function __toString()
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
     * Converts a domain name to unicode.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-unicode
     *
     * @return Host|bool      Returns the domain name upon success or false on
     *                        failure.
     */
    public function domainToUnicode()
    {
        // Only strings can be valid domains. Make sure that the host is not
        // null or a network address before trying to run it through the IDNA
        // algorithm.
        if (!is_string($this->host)) {
            return false;
        }

        // Let result be the result of running Unicode ToUnicode with
        // domain_name set to domain, UseSTD3ASCIIRules set to false.
        $result = idn_to_utf8(
            $this->host,
            IDNA_NONTRANSITIONAL_TO_UNICODE,
            INTL_IDNA_VARIANT_UTS46
        );

        if ($result === false) {
            // Syntax violation
            return false;
        }

        return new self($result);
    }

    /**
     * Converts a domain name to ASCII.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param  string       $domain The domain name to be converted.
     *
     * @return string|bool           Returns the domain name upon success or
     *                               false on failure.
     */
    private static function domainToASCII($domain)
    {
        // Let result be the result of running Unicode ToASCII with domain_name
        // set to domain, UseSTD3ASCIIRules set to false, processing_option set
        // to Nontransitional_Processing, and VerifyDnsLength set to false.
        $result = idn_to_ascii(
            $domain,
            IDNA_NONTRANSITIONAL_TO_ASCII,
            INTL_IDNA_VARIANT_UTS46,
            $info
        );

        // PHP's idn_to_* functions do not offer a way to disable the
        // check on the domain's DNS length, so we work around it here by
        // returning the empty string if $domain is the empty string.
        if ($domain === '') {
            return '';
        }

        // If the conversion failed due to the length of the labels or domain
        // name, we return the result of the idn_to_* operation. There is
        // currently a bug in PHP where an overly long domain name will cause
        // the info array to be null instead of an array.
        if (!empty($info) &&
            ($info['errors'] & IDNA_ERROR_LABEL_TOO_LONG ||
            $info['errors'] & IDNA_ERROR_DOMAIN_NAME_TOO_LONG)
        ) {
            return $info['result'];
        }

        if ($result === false) {
            // Syntax violation
            return false;
        }

        return $result;
    }
}
