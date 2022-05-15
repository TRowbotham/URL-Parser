<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Math;

use Rowbot\URL\Component\Host\Math\Exception\MathException;
use Stringable;

use function floor;
use function intval;

class NativeIntAdapter implements NumberInterface, Stringable
{
    private int $number;

    public function __construct(int|string $number, int $base = 10)
    {
        $this->number = intval($number, $base);
    }

    public function intdiv(int $number): NumberInterface
    {
        return new self((int) floor($this->number / $number));
    }

    public function isEqualTo(NumberInterface $number): bool
    {
        if (!$number instanceof self) {
            throw new MathException('Must be given an instance of itself.');
        }

        return $this->number === $number->number;
    }

    public function isGreaterThan(int $number): bool
    {
        return $this->number > $number;
    }

    public function isGreaterThanOrEqualTo(NumberInterface $number): bool
    {
        if (!$number instanceof self) {
            throw new MathException('Must be given an instance of itself.');
        }

        return $this->number >= $number->number;
    }

    public function mod(int $number): NumberInterface
    {
        return new self($this->number % $number);
    }

    public function multipliedBy(int $number): NumberInterface
    {
        return new self($this->number * $number);
    }

    public function plus(NumberInterface $number): NumberInterface
    {
        if (!$number instanceof self) {
            throw new MathException('Must be given an instance of itself.');
        }

        return new self($this->number + $number->number);
    }

    public function pow(int $number): NumberInterface
    {
        return new self($this->number ** $number);
    }

    /**
     * @return numeric-string
     */
    public function __toString(): string
    {
        return (string) $this->number;
    }
}
