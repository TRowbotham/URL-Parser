<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use stdClass;

class URLTest extends TestCase
{
    public function testCloningUrl(): void
    {
        $url1 = new URL('http://127.0.0.1');
        $url2 = clone $url1;
        $url2->href = 'https://foo:bar@foo.com/foo/bar/?foo=bar#foo';

        self::assertSame('http:', $url1->protocol);
        self::assertEmpty($url1->username);
        self::assertEmpty($url1->password);
        self::assertSame('127.0.0.1', $url1->host);
        self::assertSame('127.0.0.1', $url1->hostname);
        self::assertEmpty($url1->port);
        self::assertSame('/', $url1->pathname);
        self::assertEmpty($url1->search);
        self::assertEmpty($url1->hash);
    }

    /**
     * Test variations of percent encoded dot path segements not covered by the WHATWG tests.
     */
    public function testPercentEncodedDotPathSegments(): void
    {
        $url = new URL('http://example.com/foo/bar/%2e%2E/%2E%2e');
        self::assertSame('http://example.com/', $url->href);
        self::assertSame('/', $url->pathname);
    }

    public function testInvalidGetterPropertyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $url = new URL('http://example.com');
        $url->nonExistantProperty;
    }

    public function testInvalidSetterPropertyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $url = new URL('http://example.com');
        $url->nonExistantProperty = 'foo';
    }

    public function testHrefSetterFailure(): void
    {
        $this->expectException(TypeError::class);
        $url = new URL('http://example.com');
        $url->href = 'foo';
    }

    public function testCastingURLObjectToString(): void
    {
        $url = new URL('http://example.com');
        self::assertSame('http://example.com/', (string) $url);
        self::assertSame('http://example.com/', $url->toString());
    }

    public function testHrefSetterWithNoQueryString(): void
    {
        $url = new URL('http://example.com');
        $url->href = 'ssh://example.org';
        self::assertSame('ssh://example.org', $url->href);
    }

    public function testValidLoggerDoesNotThrow(): void
    {
        $url = 'https://example.com';
        self::assertInstanceOf(URL::class, new URL($url, null, []));
        self::assertInstanceOf(URL::class, new URL($url, null, ['logger' => null]));
        self::assertInstanceOf(URL::class, new URL($url, null, ['logger' => new ValidationErrorLogger()]));
    }

    /**
     * @dataProvider invalidLoggerProvider
     */
    public function testInvalidLoggerThrows($value): void
    {
        $this->expectException(TypeError::class);
        new URL('https://example.com', null, ['logger' => $value]);
    }

    public function invalidLoggerProvider(): array
    {
        return [
            ['foo'],
            [true],
            [2],
            [new stdClass()],
        ];
    }
}
