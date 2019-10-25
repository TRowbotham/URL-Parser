<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use GMP;
use Rowbot\URL\Component\Host\Serializer\HostSerializerInterface;
use Rowbot\URL\Component\Host\Serializer\IPv4AddressSerializer;

use function gmp_cmp;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4
 */
class IPv4Address extends AbstractHost implements HostInterface
{
    /**
     * @var \GMP
     */
    private $address;

    public function __construct(GMP $address)
    {
        $this->address = $address;
    }

    public function equals(HostInterface $other): bool
    {
        return $other instanceof self && gmp_cmp($this->address, $other->address) === 0;
    }

    public function getSerializer(): HostSerializerInterface
    {
        return new IPv4AddressSerializer($this->address);
    }

    public function __clone()
    {
        $this->address = clone $this->address;
    }
}
