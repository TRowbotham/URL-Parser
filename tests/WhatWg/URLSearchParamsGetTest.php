<?php
namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\URLSearchParams;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-get.html
 */
class URLSearchParamsGetTest extends TestCase
{
    public function testGetBasics(): void
    {
        $params = new URLSearchParams('a=b&c=d');
        $this->assertEquals('b', $params->get('a'));
        $this->assertEquals('d', $params->get('c'));
        $this->assertNull($params->get('e'));
        $params = new URLSearchParams('a=b&c=d&a=e');
        $this->assertEquals('b', $params->get('a'));
        $params = new URLSearchParams('=b&c=d');
        $this->assertEquals('b', $params->get(''));
        $params = new URLSearchParams('a=&c=d&a=e');
        $this->assertEquals('', $params->get('a'));
    }

    public function testMoreGetBasics(): void
    {
        $params = new URLSearchParams('first=second&third&&');

        $this->assertNotNull($params);
        $this->assertTrue($params->has('first'), 'constructor returned non-null value.');
        $this->assertEquals('second', $params->get('first'), 'Search params object has name "first"');
        $this->assertEquals('', $params->get('third'), 'Search params object has name "third" with the empty value.');
        $this->assertNull($params->get('fourth'), 'Search params object has no "fourth" name and value.');
    }
}