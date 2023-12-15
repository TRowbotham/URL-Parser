<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests\Math;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Rowbot\URL\Component\Host\Math\NumberInterface;

use const PHP_INT_MAX;

abstract class MathTestCase extends TestCase
{
    abstract public function createNumber(int|string $number, int $base = 10): NumberInterface;

    #[TestWith([2, '21'])]
    #[TestWith([0x0D, '3'])]
    #[TestWith([2, '21'])]
    #[TestWith([24, '1'])]
    #[TestWith([-24, '-2'])]
    public function testIntDiv(int $divisor, string $quoient): void
    {
        $dividend = $this->createNumber(42);
        $computedQuotient = $dividend->intdiv($divisor);

        self::assertTrue($computedQuotient->isEqualTo($this->createNumber($quoient)));
        self::assertSame($quoient, (string) $computedQuotient);
    }

    public static function equalityNumberProvider(): array
    {
        return [
            [PHP_INT_MAX, 10, (string) PHP_INT_MAX],
            ['01234567', 8, '342391'],
            ['DF', 16, '223'],
            ['-24', 10, '-24'],
        ];
    }

    #[DataProvider('equalityNumberProvider')]
    public function testIsEqualTo(int|string $number, int $base, string $expected): void
    {
        self::assertTrue($this->createNumber($number, $base)->isEqualTo($this->createNumber($expected)));
    }

    #[TestWith([42, 9000, false])]
    #[TestWith([9000, 42, true])]
    #[TestWith([42, 42, false])]
    #[TestWith([-2, -3, true])]
    public function testIsGreaterThan(int $number1, int $number2, bool $result): void
    {
        self::assertSame($result, $this->createNumber($number1)->isGreaterThan($number2));
    }

    #[TestWith([42, 9000, false])]
    #[TestWith([9000, 42, true])]
    #[TestWith([42, 42, true])]
    #[TestWith([-2, -3, true])]
    public function testIsGreaterThanOrEqualTo(int $number1, int $number2, bool $result): void
    {
        self::assertSame($result, $this->createNumber($number1)->isGreaterThanOrEqualTo($this->createNumber($number2)));
    }

    #[TestWith([2, 2, 0])]
    #[TestWith([5, 3, 2])]
    #[TestWith([17, 6, 5])]
    public function testMod(int $dividend, int $divisor, int $remainder): void
    {
        $computedRemainder = $this->createNumber($dividend)->mod($divisor);

        self::assertTrue($computedRemainder->isEqualTo($this->createNumber($remainder)));
        self::assertSame((string) $remainder, (string) $computedRemainder);
    }

    #[TestWith([7, 2, 14])]
    #[TestWith([5, 3, 15])]
    #[TestWith([17, 6, 102])]
    #[TestWith([-4, 3, -12])]
    public function testMultipliedBy(int $multiplicand, int $multiplier, int $product): void
    {
        $computedProduct = $this->createNumber($multiplicand)->multipliedBy($multiplier);

        self::assertTrue($computedProduct->isEqualTo($this->createNumber($product)));
        self::assertSame((string) $product, (string) $computedProduct);
    }

    #[TestWith([6, 6, '12'])]
    #[TestWith([4, -3, '1'])]
    #[TestWith([-256, 6, '-250'])]
    public function testPlus(int $addend1, int $addend2, string $sum): void
    {
        $computedSum = $this->createNumber($addend1)->plus($this->createNumber($addend2));

        self::assertTrue($computedSum->isEqualTo($this->createNumber($sum)));
        self::assertSame($sum, (string) $computedSum);
    }

    #[TestWith([256, 2, '65536'])]
    #[TestWith([2, 2, '4'])]
    public function testPow(int $base, int $exponent, string $power): void
    {
        $computedPower = $this->createNumber($base)->pow($exponent);

        self::assertTrue($computedPower->isEqualTo($this->createNumber($power)));
        self::assertSame($power, (string) $computedPower);
    }

    #[DataProvider('equalityNumberProvider')]
    public function testToString(int|string $number, int $base, string $result): void
    {
        self::assertSame($result, (string) $this->createNumber($number, $base));
    }
}
