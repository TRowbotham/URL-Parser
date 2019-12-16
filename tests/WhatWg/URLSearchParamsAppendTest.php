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
        $this->assertEquals('a=b', $params . '');
        $params->append('a', 'b');
        $this->assertEquals('a=b&a=b', $params . '');
        $params->append('a', 'c');
        $this->assertEquals('a=b&a=b&a=c', $params . '');
    }

    public function testAppendEmptyString(): void
    {
        $params = new URLSearchParams();
        $params->append('', '');
        $this->assertEquals('=', $params . '');
        $params->append('', '');
        $this->assertEquals('=&=', $params . '');
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
        $this->assertEquals('1', $params->get('first'));
        $this->assertEquals('2', $params->get('second'));
        $this->assertEquals('', $params->get('third'));
        $params->append('first', 10);
        $this->assertEquals('1', $params->get('first'));
    }
}
