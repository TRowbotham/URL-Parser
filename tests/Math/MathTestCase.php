<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests\Math;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Component\Host\Math\NumberInterface;

use const PHP_INT_MAX;

abstract class MathTestCase extends TestCase
{
    /**
     * @param int|string $number
     */
    abstract public function createNumber($number, int $base = 10): NumberInterface;

    public function intdivNumberProvider(): array
    {
        return [
            [2, '21'],
            [0x0D, '3'],
            [2, '21'],
            [24, '1'],
            [-24, '-2'],
        ];
    }

    /**
     * @dataProvider intdivNumberProvider
     */
    public function testIntDiv(int $divisor, string $quoient): void
    {
        $dividend = $this->createNumber(42);
        $computedQuotient = $dividend->intdiv($divisor);

        $this->assertTrue($computedQuotient->isEqualTo($this->createNumber($quoient)));
        $this->assertSame($quoient, (string) $computedQuotient);
    }

    public function equalityNumberProvider(): array
    {
        return [
            [PHP_INT_MAX, 10, (string) PHP_INT_MAX],
            ['01234567', 8, '342391'],
            ['DF', 16, '223'],
            ['-24', 10, '-24'],
        ];
    }

    /**
     * @dataProvider equalityNumberProvider
     *
     * @param $number int|string
     */
    public function testIsEqualTo($number, int $base, string $expected): void
    {
        $this->assertTrue($this->createNumber($number, $base)->isEqualTo($this->createNumber($expected)));
    }

    public function greaterThanNumberProvider(): array
    {
        return [
            [42, 9000, false],
            [9000, 42, true],
            [42, 42, false],
            [-2, -3, true],
        ];
    }

    /**
     * @dataProvider greaterThanNumberProvider
     */
    public function testIsGreaterThan(int $number1, int $number2, bool $result): void
    {
        $this->assertSame($result, $this->createNumber($number1)->isGreaterThan($number2));
    }

    public function greaterThanOrEqualToNumberProvider(): array
    {
        return [
            [42, 9000, false],
            [9000, 42, true],
            [42, 42, true],
            [-2, -3, true],
        ];
    }

    /**
     * @dataProvider greaterThanOrEqualToNumberProvider
     */
    public function testIsGreaterThanOrEqualTo(int $number1, int $number2, bool $result): void
    {
        $this->assertSame($result, $this->createNumber($number1)->isGreaterThanOrEqualTo($this->createNumber($number2)));
    }

    public function modNumberProvider(): array
    {
        return [
            [2, 2, 0],
            [5, 3, 2],
            [17, 6, 5],
        ];
    }

    /**
     * @dataProvider modNumberProvider
     */
    public function testMod(int $dividend, int $divisor, int $remainder): void
    {
        $computedRemainder = $this->createNumber($dividend)->mod($divisor);

        $this->assertTrue($computedRemainder->isEqualTo($this->createNumber($remainder)));
        $this->assertSame((string) $remainder, (string) $computedRemainder);
    }

    public function multipliedByNumberProvider(): array
    {
        return [
            [7, 2, 14],
            [5, 3, 15],
            [17, 6, 102],
            [-4, 3, -12],
        ];
    }

    /**
     * @dataProvider multipliedByNumberProvider
     */
    public function testMultipliedBy(int $multiplicand, int $multiplier, int $product): void
    {
        $computedProduct = $this->createNumber($multiplicand)->multipliedBy($multiplier);

        $this->assertTrue($computedProduct->isEqualTo($this->createNumber($product)));
        $this->assertSame((string) $product, (string) $computedProduct);
    }

    public function additionNumberProvider(): array
    {
        return [
            [6, 6, '12'],
            [4, -3, '1'],
            [-256, 6, '-250'],
        ];
    }

    /**
     * @dataProvider additionNumberProvider
     */
    public function testPlus(int $addend1, int $addend2, string $sum): void
    {
        $computedSum = $this->createNumber($addend1)->plus($this->createNumber($addend2));

        $this->assertTrue($computedSum->isEqualTo($this->createNumber($sum)));
        $this->assertSame($sum, (string) $computedSum);
    }

    public function powerNumberProvider(): array
    {
        return [
            [256, 2, '65536'],
            [2, 2, '4'],
        ];
    }

    /**
     * @dataProvider powerNumberProvider
     */
    public function testPow(int $base, int $exponent, string $power): void
    {
        $computedPower = $this->createNumber($base)->pow($exponent);

        $this->assertTrue($computedPower->isEqualTo($this->createNumber($power)));
        $this->assertSame($power, (string) $computedPower);
    }

    /**
     * @dataProvider equalityNumberProvider
     *
     * @param int|string $number
     */
    public function testToString($number, int $base, string $result): void
    {
        $this->assertSame($result, (string) $this->createNumber($number, $base));
    }
}
