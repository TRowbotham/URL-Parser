<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Math;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

use function is_string;

class BrickMathAdapter implements NumberInterface
{
    /**
     * @var \Brick\Math\BigInteger
     */
    private $number;

    /**
     * @param int|string|\Brick\Math\BigInteger $number
     */
    public function __construct($number, int $base = 10)
    {
        if (is_string($number)) {
            $this->number = BigInteger::fromBase($number, $base);

            return;
        }

        $this->number = BigInteger::of($number);
    }

    public function add(NumberInterface $number): NumberInterface
    {
        return new self($this->number->plus($number->toInt()));
    }

    public function intdiv(int $number): NumberInterface
    {
        return new self($this->number->dividedBy($number, RoundingMode::FLOOR));
    }

    public function isEqualTo(NumberInterface $number): bool
    {
        return $this->number->isEqualTo((string) $number);
    }

    public function isGreaterThan(int $number): bool
    {
        return $this->number->isGreaterThan($number);
    }

    public function isGreaterThanOrEqualTo(int $number): bool
    {
        return $this->number->isGreaterThanOrEqualTo($number);
    }

    public function mod(int $number): NumberInterface
    {
        return new self($this->number->mod($number));
    }

    public function multipliedBy(int $number): NumberInterface
    {
        return new self($this->number->multipliedBy($number));
    }

    public function toInt(): int
    {
        return $this->number->toInt();
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }
}
