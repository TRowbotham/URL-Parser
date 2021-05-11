<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-append.any.js
 */
class URLSearchParamsAppendTest extends TestCase
{
    public function testAppendSameName(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b');
        $this->assertSame('a=b', $params . '');
        $params->append('a', 'b');
        $this->assertSame('a=b&a=b', $params . '');
        $params->append('a', 'c');
        $this->assertSame('a=b&a=b&a=c', $params . '');
    }

    public function testAppendEmptyString(): void
    {
        $params = new URLSearchParams();
        $params->append('', '');
        $this->assertSame('=', $params . '');
        $params->append('', '');
        $this->assertSame('=&=', $params . '');
        $params->append('a', 'c');
    }

    public function testAppendMultiple(): void
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        $params->append('second', 2);
        $params->append('third', '');
        $params->append('first', 10);
        $this->assertTrue($params->has('first'));
        $this->assertSame('1', $params->get('first'));
        $this->assertSame('2', $params->get('second'));
        $this->assertSame('', $params->get('third'));
        $params->append('first', 10);
        $this->assertSame('1', $params->get('first'));
    }
}
