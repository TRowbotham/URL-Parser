<?php

namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-searchparams.any.js
 */
class URLSearchParamsTest extends WhatwgTestCase
{
    public function testURLSearchParamsGetter(): void
    {
        $url = new URL('http://example.org/?a=b');
        $this->assertNotNull($url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertSame($searchParams, $url->searchParams);
    }

    /**
     * Test URL.searchParams updating, clearing.
     */
    public function testURLSearchParamsUpdatingClearing(): void
    {
        $url = new URL('http://example.org/?a=b', 'about:blank');
        $this->assertNotNull($url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertSame('a=b', $searchParams->toString());

        $searchParams->set('a', 'b');
        $this->assertSame('a=b', $url->searchParams->toString());
        $this->assertSame('?a=b', $url->search);
        $url->search = '';
        $this->assertSame('', $url->searchParams->toString());
        $this->assertSame('', $url->search);
        $this->assertSame('', $searchParams->toString());
    }

    public function testURLSearchParamsSetterInvalidValues(): void
    {
        $this->expectException(TypeError::class);
        $urlString = 'http://example.org';
        $url = new URL($urlString, 'about:blank');
        $url->searchParams = new URLSearchParams($urlString);
    }

    public function testURLSearchParamsAndURLSearchSettersUpdatePropagation(): void
    {
        $url = new URL('http://example.org/file?a=b&c=d');
        $this->assertInstanceOf(URLSearchParams::class, $url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertSame('?a=b&c=d', $url->search);
        $this->assertSame('a=b&c=d', $searchParams->toString());

        // Test that setting 'search' propagates to the URL object's query
        // object
        $url->search = 'e=f&g=h';
        $this->assertSame('?e=f&g=h', $url->search);
        $this->assertSame('e=f&g=h', $url->searchParams->toString());

        // ...and same, but with a leading '?'
        $url->search = '?e=f&g=h';
        $this->assertSame('?e=f&g=h', $url->search);
        $this->assertSame('e=f&g=h', $url->searchParams->toString());

        // And in the other direction, altering searchParams propagates back
        // to 'search'
        $searchParams->append('i', ' j ');
        $this->assertSame('?e=f&g=h&i=+j+', $url->search);
        $this->assertSame('e=f&g=h&i=+j+', $url->searchParams->toString());
        $this->assertSame(' j ', $searchParams->get('i'));

        $searchParams->set('e', 'updated');
        $this->assertSame('?e=updated&g=h&i=+j+', $url->search);
        $this->assertSame('e=updated&g=h&i=+j+', $url->searchParams->__toString());

        $url2 = new URL('http://example.org/file??a=b&c=d', 'about:blank');
        $this->assertSame('??a=b&c=d', $url2->search);
        $this->assertSame('%3Fa=b&c=d', $url2->searchParams->toString());

        $url2->href = 'http://example.org/file??a=b';
        $this->assertSame('??a=b', $url2->search);
        $this->assertSame('%3Fa=b', $url2->searchParams->toString());
    }
}
