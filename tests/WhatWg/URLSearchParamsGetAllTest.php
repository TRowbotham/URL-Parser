<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-getall.any.js
 */
class URLSearchParamsGetAllTest extends TestCase
{
    public function testGetAllBasics(): void
    {
        $params = new URLSearchParams('a=b&c=d');
        $this->assertEquals(['b'], $params->getAll('a'));
        $this->assertEquals(['d'], $params->getAll('c'));
        $this->assertEquals([], $params->getAll('e'));
        $params = new URLSearchParams('a=b&c=d&a=e');
        $this->assertEquals(['b', 'e'], $params->getAll('a'));
        $params = new URLSearchParams('=b&c=d');
        $this->assertEquals(['b'], $params->getAll(''));
        $params = new URLSearchParams('a=&c=d&a=e');
        $this->assertEquals(['', 'e'], $params->getAll('a'));
    }

    public function testGetAllMultiple(): void
    {
        $params = new URLSearchParams('a=1&a=2&a=3&a');
        $this->assertTrue($params->has('a'));
        $matches = $params->getAll('a');
        $this->assertTrue($matches && count($matches) === 4);
        $this->assertEquals(['1', '2', '3', ''], $matches);
        $params->set('a', 'one');
        $this->assertEquals('one', $params->get('a'));
        $matches = $params->getAll('a');
        $this->assertTrue($matches && count($matches) === 1);
        $this->assertEquals(['one'], $matches);
    }
}
