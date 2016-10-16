<?php
namespace phpjs\urls;

abstract class HostFactory
{
    /**
     * Parses a host.
     *
     * @see https://url.spec.whatwg.org/#concept-host-parser
     *
     * @param string $aInput A IPv4, IPv6 address, or a domain.
     *
     * @param bool|null $aUnicodeFlag Optional argument, that when set to true,
     *     causes the domain to be encoded using unicode instead of ASCII.
     *     Default is null.
     *
     * @return Host|string|bool Returns a Host if it was successfully parsed or
     *     false if parsing fails.
     */
    public static function parse($aInput, $aUnicodeFlag = null)
    {
        if ($aInput !== '' && $aInput[0] === '[') {
            $len = strlen($aInput);

            if ($aInput[$len - 1] !== ']') {
                // Syntax violation
                return false;
            }

            return IPv6Address::parse(substr($aInput, 1, $len - 2));
        }

        // TODO: Let domain be the result of utf-8 decode without BOM on the
        // percent decoding of utf-8 encode on input
        $domain = URLUtils::percentDecode(URLUtils::encode($aInput));
        $asciiDomain = URLUtils::domainToASCII($domain);

        if ($asciiDomain === false) {
            return false;
        }

        if (preg_match(URLUtils::REGEX_ASCII_DOMAIN, $asciiDomain)) {
            // Syntax violation
            return false;
        }

        $ipv4Host = IPv4Address::parse($asciiDomain);

        if ($ipv4Host instanceof IPv4Address || $ipv4Host === false) {
            return $ipv4Host;
        }

        return $aUnicodeFlag ?
            URLUtils::domainToUnicode($asciiDomain) : $asciiDomain;
    }

    /**
     * Serializes a host.
     *
     * @see https://url.spec.whatwg.org/#concept-host-serializer
     *
     * @param  Host|string $aHost A domain or an IPv4 or IPv6 address.
     *
     * @return string
     */
    public static function serialize($aHost)
    {
        if ($aHost instanceof IPv4Address) {
            return $aHost->serialize();
        }

        if ($aHost instanceof IPv6Address) {
            return '[' . $aHost->serialize() . ']';
        }

        return $aHost;
    }
}
