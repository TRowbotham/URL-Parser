<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-delete.any.js
 */
class URLSearchParamsDeleteTest extends TestCase
{
    public function testDeleteBasics(): void
    {
        $params = new URLSearchParams('a=b&c=d');
        $params->delete('a');
        $this->assertSame('c=d', $params . '');
        $params = new URLSearchParams('a=a&b=b&a=a&c=c');
        $params->delete('a');
        $this->assertSame('b=b&c=c', $params . '');
        $params = new URLSearchParams('a=a&=&b=b&c=c');
        $params->delete('');
        $this->assertSame('a=a&b=b&c=c', $params . '');
    }

    public function testDeleteAppendMultiple(): void
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        $this->assertTrue($params->has('first'));
        $this->assertSame('1', $params->get('first'));
        $params->delete('first');
        $this->assertFalse($params->has('first'));
        $params->append('first', 1);
        $params->append('first', 10);
        $params->delete('first');
        $this->assertFalse($params->has('first'));
    }

    public function testDeleteAllRemovesQuestionMark(): void
    {
        $url = new URL('http://example.com/?param1&param2');
        $url->searchParams->delete('param1');
        $url->searchParams->delete('param2');
        $this->assertSame('http://example.com/', $url->href);
        $this->assertSame('', $url->search);
    }

    public function testDeleteNonExistentParamRemovesQuestionMark(): void
    {
        $url = new URL('http://example.com/?');
        $url->searchParams->delete('param1');
        $this->assertSame('http://example.com/', $url->href);
        $this->assertSame('', $url->search);
    }
}
