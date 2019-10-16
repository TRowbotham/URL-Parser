<?php

declare(strict_types=1);

namespace Rowbot\URL;

interface NetworkAddress
{
    /**
     * Checks to see if two NetworkAddresses are equal.
     *
     * @param self|string|null $address Another NetworkAddress, string, or null.
     */
    public function equals($address): bool;

    /**
     * Serializes a network address.
     */
    public function __toString(): string;
}
