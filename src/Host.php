<?php
namespace phpjs\urls;

class Host
{
    const DOMAIN      = 1;
    const IPV4        = 2;
    const IPV6        = 3;
    const OPAQUE_HOST = 4;

    const FORBIDDEN_HOST_CODEPOINT = '/[\x00\x09\x0A\x0D\x20#%\/:?@[\\\\\]]/u';

    private $host;
    private $type;

    protected function __construct($host, $type)
    {
        $this->host = $host;
        $this->type = $type;
    }

    public function __clone()
    {
        if ($this->type == self::IPV4 || $this->type == self::IPV6) {
            $this->host = clone $this->host;
        }
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
     *                   false if parsing fails.
     */
    public static function parse($input, $isSpecial)
    {
        if (mb_substr($input, 0, 1, 'UTF-8') === '[') {
            if (mb_substr($input, -1, null, 'UTF-8') !== ']') {
                // Syntax violation
                return false;
            }

            $ipv6 = IPv6Address::parse(mb_substr($input, 1, -1, 'UTF-8'));

            return $ipv6 === false ? false : new self($ipv6, self::IPV6);
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
            return new self($ipv4Host, self::IPV4);
        }

        if ($ipv4Host === false) {
            return $ipv4Host;
        }

        return new self($asciiDomain, self::DOMAIN);
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

        return new self($output, self::OPAQUE_HOST);
    }

    /**
     * Creates a new domain that is the empty string.
     *
     * @return Host
     */
    public static function createEmptyDomain()
    {
        return new self('', self::DOMAIN);
    }

    /**
     * Returns whether or not a Host is a particlar type.
     *
     * @param  int  $type A Host type.
     *
     * @return bool
     */
    public function isType($type)
    {
        return $this->type == $type;
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
        $value = $host;
        $typeCheck = true;

        if ($host instanceof self) {
            $value = $host->host;
            $typeCheck = $this->type == $host->type;
        }

        switch ($this->type) {
            case self::DOMAIN:
            case self::OPAQUE_HOST:
                return $typeCheck &&
                    is_string($value) &&
                    $this->host === $value;

            case self::IPV4:
            case self::IPV6:
                return $typeCheck && $this->host->equals($value);
        }

        return false;
    }

    /**
     * Sets the host to a new value for domains and opaque hosts.
     *
     * @param  string A new host value.
     */
    public function setHost($host)
    {
        if (!is_string($host)) {
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
        if ($this->type == self::IPV4) {
            return (string) $this->host;
        }

        if ($this->type == self::IPV6) {
            return '[' . $this->host . ']';
        }

        return $this->host;
    }

    /**
     * Converts a domain name to unicode.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-unicode
     *
     * @param  string $domain The domain name to be converted.
     *
     * @return Host|bool      Returns the domain name upon success or false on
     *                        failure.
     */
    public function domainToUnicode()
    {
        // Let result be the result of running Unicode ToUnicode with
        // domain_name set to domain, UseSTD3ASCIIRules set to false.
        $result = idn_to_utf8(
            $this->host,
            IDNA_NONTRANSITIONAL_TO_UNICODE,
            INTL_IDNA_VARIANT_UTS46,
            $info
        );

        if ($result === false) {
            // Syntax violation
            return false;
        }

        return new self($result, self::DOMAIN);
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
        $domain = $domain;

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
