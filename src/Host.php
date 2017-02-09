<?php
namespace phpjs\urls;

class Host
{
    const DOMAIN      = 1;
    const IPV4        = 2;
    const IPV6        = 3;
    const OPAQUE_HOST = 4;

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
     * @param  bool   $unicode   Optional argument, that when set to true,
     *                           causes the domain to be encoded using unicode
     *                           instead of ASCII.
     *
     * @return Host|bool Returns a Host if it was successfully parsed or
     *                   false if parsing fails.
     */
    public static function parse($input, $unicode = false)
    {
        if (mb_substr($input, 0, 1, 'UTF-8') === '[') {
            if (mb_substr($input, -1, null, 'UTF-8') !== ']') {
                // Syntax violation
                return false;
            }

            $ipv6 = IPv6Address::parse(mb_substr($input, 1, -1, 'UTF-8'));

            return $ipv6 === false ? false : new self($ipv6, self::IPV6);
        }

        // TODO: Let domain be the result of utf-8 decode without BOM on the
        // percent decoding of utf-8 encode on input
        $domain = URLUtils::percentDecode(URLUtils::encode($input));
        $asciiDomain = URLUtils::domainTo('ascii', $domain);

        if ($asciiDomain === false) {
            return false;
        }

        if (preg_match(URLUtils::FORBIDDEN_HOST_CODEPOINT, $asciiDomain)) {
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
            ? URLUtils::domainTo('unicode', $asciiDomain)
            : $asciiDomain;
    }

    /**
     * @param  string $aInput
     *
     * @param  bool   $isSpecial
     *
     * @return Host|bool
     */
    public static function parseUrlHost($input, $isSpecial)
    {
        if ($isSpecial) {
            return self::parse($input);
        }

        if (preg_match(self::FORBIDDEN_HOST_CODEPOINT, $input)) {
            // Syntax violation
            return false;
        }

        $output = '';

        while (($char = mb_substr($input, 0, 1, 'UTF-8')) !== '') {
            $output .= self::utf8PercentEncode($char);
            $input = mb_substr($input, 1, null, 'UTF-8');
        }

        return new self($output, self::OPAQUE_HOST);
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
}
