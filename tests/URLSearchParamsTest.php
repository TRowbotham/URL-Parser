<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use ArrayObject;
use Countable;
use Generator;
use PHPUnit\Framework\TestCase;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;

class URLSearchParamsTest extends TestCase
{
    public function testCloningStandaloneURLSearchParams(): void
    {
        $query = new URLSearchParams('foo=bar');
        $query2 = clone $query;
        $query2->append('foo', 'bar');

        self::assertSame('foo=bar', $query->toString());
    }

    public function testCloningAttachedURLSearchParams(): void
    {
        $url = new URL('http://example.com/?foo=bar');
        $query = clone $url->searchParams;
        $query->append('foo', 'bar');

        self::assertSame('?foo=bar', $url->search);
        self::assertSame('foo=bar', $url->searchParams->toString());
    }

    public function testIterationKey(): void
    {
        $query = new URLSearchParams('foo=bar&qux=baz');
        $result = [
            ['foo', 'bar'],
            ['qux', 'baz'],
        ];

        foreach ($query as $index => $pair) {
            self::assertSame($result[$index], $pair);
        }
    }

    public function getInvalidIteratorInput(): array
    {
        $generator = (static function (): Generator {
            yield 'foo';
            yield 'bar';
        })();
        $anonClass = new class ($generator) implements Countable
        {
            public Generator $foo;

            public function __construct(Generator $foo)
            {
                $this->foo = $foo;
            }

            public function count(): int
            {
                return 2;
            }
        };

        return [
            'sequences not equal to 2' => [[['foo', 'bar'], ['baz']]],
            'non-iterable'             => [new ArrayObject(['x', 'y'])],
            'generator'                => [[$generator]],
            'invalid-name'             => [[[null, 'foo']]],
            'invalid-value'            => [[['foo', null]]],
            'countable-only'           => [[[$anonClass]]],
            'invalid-property-value'   => [$anonClass],
        ];
    }

    /**
     * @dataProvider getInvalidIteratorInput
     */
    public function testInvalidIteratorInput(iterable|object $input): void
    {
        $this->expectException(TypeError::class);
        new URLSearchParams($input);
    }

    public function unhandledInputProvider(): array
    {
        return [
            [static function (): void {
                return;
            }],
        ];
    }

    /**
     * @dataProvider unhandledInputProvider
     */
    public function testUnhandledInputDoesNothing($input): void
    {
        $params = new URLSearchParams($input);
        self::assertFalse($params->valid());
    }

    public function testInvalidIteratorReturnsArrayWithEmptyStrings(): void
    {
        $params = new URLSearchParams();
        self::assertSame(['', ''], $params->current());
        self::assertFalse($params->valid());
    }

    public function testSortingPairWithEmptyName(): void
    {
        $params = new URLSearchParams('=foo&x=bar&c=bar');
        $params->sort();
        self::assertSame('=foo&c=bar&x=bar', $params->toString());
    }
}
