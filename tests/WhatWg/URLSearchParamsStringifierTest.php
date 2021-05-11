<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-stringifier.any.js
 */
class URLSearchParamsStringifierTest extends TestCase
{
    public function testSerializeSpace(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b c');
        $this->assertSame('a=b+c', $params . '');
        $params->delete('a');
        $params->append('a b', 'c');
        $this->assertSame('a+b=c', $params . '');
    }

    public function testSerializeEmptyValue(): void
    {
        $params = new URLSearchParams();
        $params->append('a', '');
        $this->assertSame('a=', $params . '');
        $params->append('a', '');
        $this->assertSame('a=&a=', $params . '');
        $params->append('', 'b');
        $this->assertSame('a=&a=&=b', $params . '');
        $params->append('', '');
        $this->assertSame('a=&a=&=b&=', $params . '');
        $params->append('', '');
        $this->assertSame('a=&a=&=b&=&=', $params . '');
    }

    public function testSerializeEmptyName(): void
    {
        $params = new URLSearchParams();
        $params->append('', 'b');
        $this->assertSame('=b', $params . '');
        $params->append('', 'b');
        $this->assertSame('=b&=b', $params . '');
    }

    public function testSerialzieEmptyNameAndValue(): void
    {
        $params = new URLSearchParams();
        $params->append('', '');
        $this->assertSame('=', $params . '');
        $params->append('', '');
        $this->assertSame('=&=', $params . '');
    }

    public function testSerialziePlusSign(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b+c');
        $this->assertSame('a=b%2Bc', $params . '');
        $params->delete('a');
        $params->append('a+b', 'c');
        $this->assertSame('a%2Bb=c', $params . '');
    }

    public function testSerializeEqualSign(): void
    {
        $params = new URLSearchParams();
        $params->append('=', 'a');
        $this->assertSame('%3D=a', $params . '');
        $params->append('b', '=');
        $this->assertSame('%3D=a&b=%3D', $params . '');
    }

    public function testSerializeAmpersand(): void
    {
        $params = new URLSearchParams();
        $params->append('&', 'a');
        $this->assertSame('%26=a', $params . '');
        $params->append('b', '&');
        $this->assertSame('%26=a&b=%26', $params . '');
    }

    public function testSerializeSpecialChars(): void
    {
        $params = new URLSearchParams();
        $params->append('a', '*-._');
        $this->assertSame('a=*-._', $params . '');
        $params->delete('a');
        $params->append('*-._', 'c');
        $this->assertSame('*-._=c', $params . '');
    }

    public function testSerializePercentSign(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b%c');
        $this->assertSame('a=b%25c', $params . '');
        $params->delete('a');
        $params->append('a%b', 'c');
        $this->assertSame('a%25b=c', $params . '');

        $params = new URLSearchParams('id=0&value=%');
        $this->assertSame('id=0&value=%25', $params . '');
    }

    public function testSerializeNullByte(): void
    {
        $params = new URLSearchParams();
        $params->append('a', "b\0c");
        $this->assertSame('a=b%00c', $params . '');
        $params->delete('a');
        $params->append("a\0b", 'c');
        $this->assertSame('a%00b=c', $params . '');
    }

    public function testSerializeUnicodePileOfPoo(): void
    {
        $params = new URLSearchParams();
        $params->append('a', "b\u{1F4A9}c");
        $this->assertSame('a=b%F0%9F%92%A9c', $params . '');
        $params->delete('a');
        $params->append("a\u{1F4A9}b", 'c');
        $this->assertSame('a%F0%9F%92%A9b=c', $params . '');
    }

    public function testStringification(): void
    {
        $params = new URLSearchParams('a=b&c=d&&e&&');
        $this->assertSame('a=b&c=d&e=', $params->toString());
        $params = new URLSearchParams('a = b &a=b&c=d%20');
        $this->assertSame('a+=+b+&a=b&c=d+', $params->toString());
        // The lone '=' _does_ survive the roundtrip.
        $params = new URLSearchParams('a=&a=b');
        $this->assertSame('a=&a=b', $params->toString());

        $params = new URLSearchParams('b=%2sf%2a');
        $this->assertSame('b=%252sf*', $params->toString());

        $params = new URLSearchParams('b=%2%2af%2a');
        $this->assertSame('b=%252*f*', $params->toString());

        $params = new URLSearchParams('b=%%2a');
        $this->assertSame('b=%25*', $params->toString());
    }

    public function testURLSearchParamsConnectedToURL(): void
    {
        $url = new URL('http://www.example.com/?a=b,c');
        $params = $url->searchParams;

        $this->assertSame('http://www.example.com/?a=b,c', $url->toString());
        $this->assertSame('a=b%2Cc', $params->toString());

        $params->append('x', 'y');

        $this->assertSame(
            'http://www.example.com/?a=b%2Cc&x=y',
            $url->toString()
        );
        $this->assertSame('a=b%2Cc&x=y', $params->toString());
    }

    public function testURLSearchParamsMustNotDoNewlineNormalization(): void
    {
        $url = new URL('http://www.example.com/');
        $params = $url->searchParams;

        $params->append("a\nb", "c\rd");
        $params->append("e\n\rf", "g\r\nh");

        self::assertSame('a%0Ab=c%0Dd&e%0A%0Df=g%0D%0Ah', $params->toString());
    }
}
