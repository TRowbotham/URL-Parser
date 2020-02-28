<?php

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Component\Host\Math\BrickMathAdapter;
use Rowbot\URL\Component\Host\Math\NativeIntAdapter;
use Rowbot\URL\Component\Host\Math\NumberInterface;

class MathTest extends TestCase
{
    public function addNumberProvider(): array
    {
        return [
            [new BrickMathAdapter(42), '84'],
            [new BrickMathAdapter('0D', 16), '55'],
            [new BrickMathAdapter(-24), '18'],
            [new NativeIntAdapter(42), '84'],
            [new NativeIntAdapter('0D', 16), '55'],
            [new NativeIntAdapter(-24), '18'],
        ];
    }

    /**
     * @dataProvider addNumberProvider
     */
    public function testAdd(NumberInterface $addend, string $sum): void
    {
        $number1 = new BrickMathAdapter(42);
        $number2 = new NativeIntAdapter(42);

        $this->assertSame($sum, (string) $number1->add($addend));
        $this->assertSame($sum, (string) $number2->add($addend));
    }

    public function intdivNumberProvider(): array
    {
        return [
            [2, '21'],
            [0x0D, '3'],
            [2, '21'],
            [24, '1'],
            [-24, '-2']
        ];
    }

    /**
     * @dataProvider intdivNumberProvider
     */
    public function testIntDiv(int $divisor, string $quoient): void
    {
        $number1 = new BrickMathAdapter(42);
        $number2 = new NativeIntAdapter(42);

        $this->assertSame($quoient, (string) $number1->intdiv($divisor));
        $this->assertSame($quoient, (string) $number2->intdiv($divisor));
    }

    public function testIsEqualTo(): void
    {
        $number1 = new BrickMathAdapter(42);
        $number2 = new NativeIntAdapter(42);

        $this->assertTrue($number1->isEqualTo($number1));
        $this->assertTrue($number1->isEqualTo($number2));
        $this->assertTrue($number2->isEqualTo($number1));
        $this->assertTrue($number2->isEqualTo($number2));
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
    public function testIsGreaterThan(int $num1, int $num2, bool $result): void
    {
        $number1 = new BrickMathAdapter($num1);
        $number2 = new NativeIntAdapter($num1);

        $this->assertSame($result, $number1->isGreaterThan($num2));
        $this->assertSame($result, $number2->isGreaterThan($num2));
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
    public function testIsGreaterThanOrEqualTo(int $num1, int $num2, bool $result): void
    {
        $number1 = new BrickMathAdapter($num1);
        $number2 = new NativeIntAdapter($num1);

        $this->assertSame($result, $number1->isGreaterThanOrEqualTo($num2));
        $this->assertSame($result, $number2->isGreaterThanOrEqualTo($num2));
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
        $dividend1 = new BrickMathAdapter($dividend);
        $dividend2 = new NativeIntAdapter($dividend);

        $this->assertTrue($dividend1->mod($divisor)->isEqualTo(new BrickMathAdapter($remainder)));
        $this->assertTrue($dividend2->mod($divisor)->isEqualTo(new NativeIntAdapter($remainder)));
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
        $multiplicand1 = new BrickMathAdapter($multiplicand);
        $multiplicand2 = new NativeIntAdapter($multiplicand);

        $this->assertTrue($multiplicand1->multipliedBy($multiplier)->isEqualTo(new BrickMathAdapter($product)));
        $this->assertTrue($multiplicand2->multipliedBy($multiplier)->isEqualTo(new NativeIntAdapter($product)));
    }

    /**
     * @dataProvider multipliedByNumberProvider
     */
    public function testToInt(int $number): void
    {
        $this->assertSame($number, (new BrickMathAdapter($number))->toInt());
        $this->assertSame($number, (new NativeIntAdapter($number))->toInt());
    }

    /**
     * @dataProvider multipliedByNumberProvider
     */
    public function testToString(int $number): void
    {
        $this->assertSame((string) $number, (string) new BrickMathAdapter($number));
        $this->assertSame((string) $number, (string) new NativeIntAdapter($number));
    }
}
