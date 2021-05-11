<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-foreach.any.js
 */
class URLSearchParamsForeachTest extends TestCase
{
    public function test1(): void
    {
        $params = new URLSearchParams('a=1&b=2&c=3');
        $keys = [];
        $values = [];

        foreach ($params as $param) {
            $keys[] = $param[0];
            $values[] = $param[1];
        }

        $this->assertSame(['a', 'b', 'c'], $keys);
        $this->assertSame(['1', '2', '3'], $values);
    }

    public function test2(): void
    {
        $a = new URL('http://a.b/c?a=1&b=2&c=3&d=4');
        $b = $a->searchParams;
        $c = [];

        foreach ($b as $i) {
            $a->search = 'x=1&y=2&z=3';
            $c[] = $i;
        }

        $this->assertSame(['a', '1'], $c[0]);
        $this->assertSame(['y', '2'], $c[1]);
        $this->assertSame(['z', '3'], $c[2]);
    }

    public function test3(): void
    {
        $a = new URL('http://a.b/c');
        $b = $a->searchParams;
        $this->assertFalse($b->valid());
    }

    public function testDeleteNextParamDuringIteration(): void
    {
        $url = new URL('http://localhost/query?param0=0&param1=1&param2=2');
        $searchParams = $url->searchParams;
        $seen = [];

        foreach ($searchParams as $param) {
            if ($param[0] === 'param0') {
                $searchParams->delete('param1');
            }

            $seen[] = $param;
        }

        $this->assertSame(['param0', '0'], $seen[0]);
        $this->assertSame(['param2', '2'], $seen[1]);
    }

    public function testDeleteCurrentParamDuringIteration(): void
    {
        $url = new URL('http://localhost/query?param0=0&param1=1&param2=2');
        $searchParams = $url->searchParams;
        $seen = [];

        foreach ($searchParams as $param) {
            if ($param[0] === 'param0') {
                $searchParams->delete('param1');
            // 'param1=1' is now in the first slot, so the next iteration will see 'param2=2'.
            } else {
                $seen[] = $param;
            }
        }

        $this->assertSame(['param2', '2'], $seen[0]);
    }

    public function testDeleteEveryParamSeenDuringIteration(): void
    {
        $url = new URL('http://localhost/query?param0=0&param1=1&param2=2');
        $searchParams = $url->searchParams;
        $seen = [];

        foreach ($searchParams as $param) {
            $seen[] = $param[0];
            $searchParams->delete($param[0]);
        }

        $this->assertSame(['param0', 'param2'], $seen);
        $this->assertSame('param1=1', (string) $searchParams);
    }
}
