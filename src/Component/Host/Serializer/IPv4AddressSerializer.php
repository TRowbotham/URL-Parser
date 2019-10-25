<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Serializer;

use GMP;

use function gmp_div_q;

use const GMP_ROUND_MINUSINF;

/**
 * @see https://url.spec.whatwg.org/#concept-ipv4-serializer
 */
class IPv4AddressSerializer implements HostSerializerInterface
{
    /**
     * @var \GMP
     */
    private $address;

    public function __construct(GMP $address)
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
            $output = ($number % 256) . $output;

            if ($i < 3) {
                $output = '.' . $output;
            }

            $number = gmp_div_q($number, 256, GMP_ROUND_MINUSINF);
        }

        return $output;
    }

    public function __clone()
    {
        $this->address = clone $this->address;
    }
}
