<?php
namespace Rowbot\URL;

interface NetworkAddress
{
    /**
     * Checks to see if two NetworkAddresses are equal.
     *
     * @param self|string $address Another NetworkAddress or a string.
     *
     * @return bool
     */
    public function equals($address);

    /**
     * Serializes a network address.
     *
     * @return string
     */
    public function __toString();
}
