<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Math;

use function floor;
use function intval;

class NativeIntAdapter implements NumberInterface
{
    /**
     * @var int
     */
    private $number;

    /**
     * @param string|int $number
     */
    public function __construct($number, int $base = 10)
    {
        $this->number = intval($number, $base);
    }

    public function add(NumberInterface $number): NumberInterface
    {
        if ($number instanceof self) {
            return new self($this->number + $number->number);
        }

        return new self($this->number + $number->toInt());
    }

    public function intdiv(int $number): NumberInterface
    {
        return new self((int) floor($this->number / $number));
    }

    public function isEqualTo(NumberInterface $number): bool
    {
        if ($number instanceof self) {
            return $this->number === $number->number;
        }

        return (string) $this->number === (string) $number;
    }

    public function isGreaterThan(int $number): bool
    {
        return $this->number > $number;
    }

    public function isGreaterThanOrEqualTo(int $number): bool
    {
        return $this->number >= $number;
    }

    public function mod(int $number): NumberInterface
    {
        return new self($this->number % $number);
    }

    public function multipliedBy(int $number): NumberInterface
    {
        return new self($this->number * $number);
    }

    public function toInt(): int
    {
        return $this->number;
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }
}
