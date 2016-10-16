<?php
namespace phpjs\urls;

class IPv6Address extends Host
{
    protected function __construct($aHost)
    {
        parent::__construct($aHost);
    }

    /**
     * Parses an IPv6 string.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-parser
     *
     * @param string $aInput An IPv6 address.
     *
     * @return IPv6Address|bool Returns an IPv6Address if the string was
     *     successfully parsed as an IPv6 address or false if the input is not
     *     an IPv6 address.
     */
    public static function parse($aInput)
    {
        $isIPv6 = filter_var($aInput, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        if ($isIPv6 !== false) {
            return new IPv6Address(inet_pton($aInput));
        }

        return false;
    }

    /**
     * Serializes an IPv6 address.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv6-serializer
     *
     * @return string
     */
    public function serialize()
    {
        return inet_ntop($this->mHost);
    }
}
