<?php
namespace phpjs\urls;

use GMP;

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

    /**
     * Parses a host.
     *
     * @see https://url.spec.whatwg.org/#concept-host-parser
     *
     * @param  string $input     An IPv4, IPv6 address, domain, or opaque host.
     *
     * @param  bool   $isSpecial Whether or not the URL has a special scheme.
     *
     * @param  bool   $unicode   Optional argument, that when set to true,
     *                           causes the domain to be encoded using unicode
     *                           instead of ASCII.
     *
     * @return Host|bool Returns a Host if it was successfully parsed or
     *                   false if parsing fails.
     */
    public static function parse($input, $isSpecial, $unicode = false)
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

        if ($ipv4Host instanceof GMP) {
            return new self($ipv4Host, self::IPV4);
        }

        if ($ipv4Host === false) {
            return $ipv4Host;
        }

        return $unicode
            ? $asciiDomain->domainToUnicode()
            : $asciiDomain;
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
     * Gets the underlying host value.
     *
     * @return GMP|array|string
     */
    public function getHost()
    {
        return $this->host;
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
            return IPv4Address::serialize($this->host);
        }

        if ($this->type == self::IPV6) {
            return '[' . IPv6Address::serialize($this->host) . ']';
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

        return self::domainToIDN($this->host, $result, $info);
    }

    /**
     * Converts a domain name to ASCII.
     *
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     *
     * @param  string $domain The domain name to be converted.
     *
     * @return Host|bool      Returns the domain name upon success or false on
     *                        failure.
     */
    public static function domainToASCII($domain)
    {
        $domain = (string) $domain;

        // Let result be the result of running Unicode ToASCII with domain_name
        // set to domain, UseSTD3ASCIIRules set to false, processing_option set
        // to Transitional_Processing, and VerifyDnsLength set to false.
        $result = idn_to_ascii(
            $domain,
            0,
            INTL_IDNA_VARIANT_UTS46,
            $info
        );

        return self::domainToIDN($domain, $result, $info);
    }

    /**
     * Processes the result of a call to an idn_to_ascii or idn_to_unicode
     * function.
     *
     * @param  string      $domain The domain that was processed.
     *
     * @param  string|bool $result The resulting string of the conversion or
     *                             false if the conversion failed.
     *
     * @param  array $info         An array containing information about the
     *                             conversion process.
     *
     * @return Host|bool
     */
    private static function domainToIDN($domain, $result, $info)
    {
        // PHP's idn_to_* functions do not offer a way to disable the
        // check on the domain's DNS length, so we work around it here by
        // returning $domain if it is the empty string.
        if ($domain === '') {
            return new self('', self::DOMAIN);
        }

        // If the conversion failed due to the length of the labels or domain
        // name, we return the result of the idn_to_* operation. There is
        // currently a bug in PHP where an overly long domain name will cause
        // the info array to be null instead of an array.
        if (!empty($info) &&
            ($info['errors'] & IDNA_ERROR_LABEL_TOO_LONG ||
            $info['errors'] & IDNA_ERROR_DOMAIN_NAME_TOO_LONG)
        ) {
            return new self($info['result'], self::DOMAIN);
        }

        if ($result === false) {
            // Syntax violation
            return false;
        }

        return new self($result, self::DOMAIN);
    }
}
