<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;

class URLTest extends TestCase
{
    public function testCloningUrl(): void
    {
        $url1 = new URL('http://127.0.0.1');
        $url2 = clone $url1;
        $url2->href = 'https://foo:bar@foo.com/foo/bar/?foo=bar#foo';

        $this->assertSame('http:', $url1->protocol);
        $this->assertEmpty($url1->username);
        $this->assertEmpty($url1->password);
        $this->assertSame('127.0.0.1', $url1->host);
        $this->assertSame('127.0.0.1', $url1->hostname);
        $this->assertEmpty($url1->port);
        $this->assertSame('/', $url1->pathname);
        $this->assertEmpty($url1->search);
        $this->assertEmpty($url1->hash);
    }

    /**
     * Test variations of percent encoded dot path segements not covered by the WHATWG tests.
     */
    public function testPercentEncodedDotPathSegments(): void
    {
        $url = new URL('http://example.com/foo/bar/%2e%2E/%2E%2e');
        $this->assertSame('http://example.com/', $url->href);
        $this->assertSame('/', $url->pathname);
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
        $this->assertSame('http://example.com/', (string) $url);
        $this->assertSame('http://example.com/', $url->toString());
    }

    public function testHrefSetterWithNoQueryString(): void
    {
        $url = new URL('http://example.com');
        $url->href = 'ssh://example.org';
        $this->assertSame('ssh://example.org', $url->href);
    }
}
