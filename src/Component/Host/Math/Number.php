<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Math;

use const PHP_INT_SIZE;

/**
 * Creates a number object based on the platform we are operating on. In the case of 32-bit PHP,
 * we must use a BigInt library since the stored representation of an IPv4 address can overflow
 * a 32-bit integer as it expects to be stored as an unsigned 32-bit integer, but PHP only
 * supports signed integers.
 */
class Number implements NumberInterface
{
    /**
     * @var \Rowbot\URL\Component\Host\Math\NumberInterface
     */
    private $number;

    /**
     * @param int|string $number
     */
    public function __construct($number, int $base)
    {
        // PHP_INT_SIZE returns the number of bytes that can fit in to an integer on the given
        // platform. If the size is 4, then we know we are operating on a 32-bit platform.
        if (PHP_INT_SIZE === 4) {
            // @codeCoverageIgnoreStart
            $this->number = new BrickMathAdapter($number, $base);

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->number = new NativeIntAdapter($number, $base);
    }

    public function intdiv(int $number): NumberInterface
    {
        return $this->number->intdiv($number);
    }

    public function isEqualTo(NumberInterface $number): bool
    {
        return $this->number->isEqualTo($number);
    }

    public function isGreaterThan(int $number): bool
    {
        return $this->number->isGreaterThan($number);
    }

    public function isGreaterThanOrEqualTo(NumberInterface $number): bool
    {
        return $this->number->isGreaterThanOrEqualTo($number);
    }

    public function mod(int $number): NumberInterface
    {
        return $this->number->mod($number);
    }

    public function multipliedBy(int $number): NumberInterface
    {
        return $this->number->multipliedBy($number);
    }

    public function plus(NumberInterface $number): NumberInterface
    {
        return $this->number->plus($number);
    }

    public function pow(int $number): NumberInterface
    {
        return $this->number->pow($number);
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }
}
