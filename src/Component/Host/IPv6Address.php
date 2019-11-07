<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Serializer\HostSerializerInterface;
use Rowbot\URL\Component\Host\Serializer\IPv6AddressSerializer;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv6
 */
class IPv6Address extends AbstractHost implements HostInterface
{
    /**
     * @var array<int, int>
     */
    private $address;

    /**
     * @param array<int, int> $address
     */
    public function __construct(array $address)
    {
        $this->address = $address;
    }

    public function equals(HostInterface $other): bool
    {
        return $other instanceof self && $this->address === $other->address;
    }

    public function getSerializer(): HostSerializerInterface
    {
        return new IPv6AddressSerializer($this->address);
    }
}
