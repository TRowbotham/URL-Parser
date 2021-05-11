<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;
use stdClass;

use function fclose;
use function fopen;

class URLSearchParamsTest extends TestCase
{
    public function testCloningStandaloneURLSearchParams(): void
    {
        $query = new URLSearchParams('foo=bar');
        $query2 = clone $query;
        $query2->append('foo', 'bar');

        $this->assertSame('foo=bar', $query->toString());
    }

    public function testCloningAttachedURLSearchParams(): void
    {
        $url = new URL('http://example.com/?foo=bar');
        $query = clone $url->searchParams;
        $query->append('foo', 'bar');

        $this->assertSame('?foo=bar', $url->search);
        $this->assertSame('foo=bar', $url->searchParams->toString());
    }

    public function testIterationKey(): void
    {
        $query = new URLSearchParams('foo=bar&qux=baz');
        $result = [
            ['foo', 'bar'],
            ['qux', 'baz'],
        ];

        foreach ($query as $index => $pair) {
            $this->assertSame($result[$index], $pair);
        }
    }

    public function getInvalidIteratorInput(): array
    {
        return [
            'sequences not equal to 2' => [[['foo', 'bar'], ['baz']]],
            'non-iterable'             => [[new stdClass()]],
        ];
    }

    /**
     * @dataProvider getInvalidIteratorInput
     */
    public function testInvalidIteratorInput(iterable $input): void
    {
        $this->expectException(TypeError::class);
        $query = new URLSearchParams($input);
    }

    public function unhandledInputProvider(): array
    {
        $resource = fopen('php://memory', 'r');
        fclose($resource);

        return [
            [null],
            [static function (): void {
                return;
            }],
            [$resource],
        ];
    }

    /**
     * @dataProvider unhandledInputProvider
     */
    public function testUnhandledInputDoesNothing($input): void
    {
        $params = new URLSearchParams($input);
        $this->assertFalse($params->valid());
    }

    public function testInvalidIteratorReturnsArrayWithEmptyStrings(): void
    {
        $params = new URLSearchParams();
        $this->assertEquals(['', ''], $params->current());
        $this->assertFalse($params->valid());
    }

    public function testSortingPairWithEmptyName(): void
    {
        $params = new URLSearchParams('=foo&x=bar&c=bar');
        $params->sort();
        $this->assertSame('=foo&c=bar&x=bar', $params->toString());
    }
}
