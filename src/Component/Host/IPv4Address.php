<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use Rowbot\URL\Component\Host\Serializer\HostSerializerInterface;
use Rowbot\URL\Component\Host\Serializer\IPv4AddressSerializer;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4
 */
class IPv4Address extends AbstractHost implements HostInterface
{
    /**
     * @var string
     */
    private $address;

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    public function equals(HostInterface $other): bool
    {
        return $other instanceof self && $this->address === $other->address;
    }

    public function getSerializer(): HostSerializerInterface
    {
        return new IPv4AddressSerializer($this->address);
    }
}
