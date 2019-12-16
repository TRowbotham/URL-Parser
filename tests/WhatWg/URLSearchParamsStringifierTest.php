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
        $this->assertEquals('a=b+c', $params . '');
        $params->delete('a');
        $params->append('a b', 'c');
        $this->assertEquals('a+b=c', $params . '');
    }

    public function testSerializeEmptyValue(): void
    {
        $params = new URLSearchParams();
        $params->append('a', '');
        $this->assertEquals('a=', $params . '');
        $params->append('a', '');
        $this->assertEquals('a=&a=', $params . '');
        $params->append('', 'b');
        $this->assertEquals('a=&a=&=b', $params . '');
        $params->append('', '');
        $this->assertEquals('a=&a=&=b&=', $params . '');
        $params->append('', '');
        $this->assertEquals('a=&a=&=b&=&=', $params . '');
    }

    public function testSerializeEmptyName(): void
    {
        $params = new URLSearchParams();
        $params->append('', 'b');
        $this->assertEquals('=b', $params . '');
        $params->append('', 'b');
        $this->assertEquals('=b&=b', $params . '');
    }

    public function testSerialzieEmptyNameAndValue(): void
    {
        $params = new URLSearchParams();
        $params->append('', '');
        $this->assertEquals('=', $params . '');
        $params->append('', '');
        $this->assertEquals('=&=', $params . '');
    }

    public function testSerialziePlusSign(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b+c');
        $this->assertEquals('a=b%2Bc', $params . '');
        $params->delete('a');
        $params->append('a+b', 'c');
        $this->assertEquals('a%2Bb=c', $params . '');
    }

    public function testSerializeEqualSign(): void
    {
        $params = new URLSearchParams();
        $params->append('=', 'a');
        $this->assertEquals('%3D=a', $params . '');
        $params->append('b', '=');
        $this->assertEquals('%3D=a&b=%3D', $params . '');
    }

    public function testSerializeAmpersand(): void
    {
        $params = new URLSearchParams();
        $params->append('&', 'a');
        $this->assertEquals('%26=a', $params . '');
        $params->append('b', '&');
        $this->assertEquals('%26=a&b=%26', $params . '');
    }

    public function testSerializeSpecialChars(): void
    {
        $params = new URLSearchParams();
        $params->append('a', '*-._');
        $this->assertEquals('a=*-._', $params . '');
        $params->delete('a');
        $params->append('*-._', 'c');
        $this->assertEquals('*-._=c', $params . '');
    }

    public function testSerializePercentSign(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b%c');
        $this->assertEquals('a=b%25c', $params . '');
        $params->delete('a');
        $params->append('a%b', 'c');
        $this->assertEquals('a%25b=c', $params . '');
    }

    public function testSerializeNullByte(): void
    {
        $params = new URLSearchParams();
        $params->append('a', "b\0c");
        $this->assertEquals('a=b%00c', $params . '');
        $params->delete('a');
        $params->append("a\0b", 'c');
        $this->assertEquals('a%00b=c', $params . '');
    }

    public function testSerializeUnicodePileOfPoo(): void
    {
        $params = new URLSearchParams();
        $params->append('a', "b\u{1F4A9}c");
        $this->assertEquals('a=b%F0%9F%92%A9c', $params . '');
        $params->delete('a');
        $params->append("a\u{1F4A9}b", 'c');
        $this->assertEquals('a%F0%9F%92%A9b=c', $params . '');
    }

    public function testStringification(): void
    {
        $params = new URLSearchParams('a=b&c=d&&e&&');
        $this->assertEquals('a=b&c=d&e=', $params->toString());
        $params = new URLSearchParams('a = b &a=b&c=d%20');
        $this->assertEquals('a+=+b+&a=b&c=d+', $params->toString());
        // The lone '=' _does_ survive the roundtrip.
        $params = new URLSearchParams('a=&a=b');
        $this->assertEquals('a=&a=b', $params->toString());
    }

    public function testURLSearchParamsConnectedToURL(): void
    {
        $url = new URL('http://www.example.com/?a=b,c');
        $params = $url->searchParams;

        $this->assertEquals('http://www.example.com/?a=b,c', $url->toString());
        $this->assertEquals('a=b%2Cc', $params->toString());

        $params->append('x', 'y');

        $this->assertEquals(
            'http://www.example.com/?a=b%2Cc&x=y',
            $url->toString()
        );
        $this->assertEquals('a=b%2Cc&x=y', $params->toString());
    }
}
