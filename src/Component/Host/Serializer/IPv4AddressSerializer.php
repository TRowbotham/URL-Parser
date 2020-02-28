<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Serializer;

use Rowbot\URL\Component\Host\Math\NumberInterface;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4-serializer
 */
class IPv4AddressSerializer implements HostSerializerInterface
{
    /**
     * @var \Rowbot\URL\Component\Host\Math\NumberInterface;
     */
    private $address;

    public function __construct(NumberInterface $address)
    {
        $this->address = $address;
    }

    public function toFormattedString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        $output = '';
        $number = $this->address;

        for ($i = 0; $i < 4; ++$i) {
            $output = $number->mod(256) . $output;

            if ($i < 3) {
                $output = '.' . $output;
            }

            $number = $number->intdiv(256);
        }

        return $output;
    }

    public function __clone()
    {
        $this->address = clone $this->address;
    }
}
