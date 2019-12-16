<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-has.any.js
 */
class URLSearchParamsHasTest extends TestCase
{
    public function testHasBasics(): void
    {
        $params = new URLSearchParams('a=b&c=d');
        $this->assertTrue($params->has('a'));
        $this->assertTrue($params->has('c'));
        $this->assertFalse($params->has('e'));
        $params = new URLSearchParams('a=b&c=d&a=e');
        $this->assertTrue($params->has('a'));
        $params = new URLSearchParams('=b&c=d');
        $this->assertTrue($params->has(''));
    }

    public function testHasFollowingDelete(): void
    {
        $params = new URLSearchParams('a=b&c=d&&');
        $params->append('first', 1);
        $params->append('first', 2);
        $this->assertTrue($params->has('a'));
        $this->assertTrue($params->has('c'));
        $this->assertTrue($params->has('first'));
        $this->assertFalse($params->has('d'));
        $params->delete('first');
        $this->assertFalse($params->has('first'));
    }
}
