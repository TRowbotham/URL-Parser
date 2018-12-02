<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-constructor.html
 */
class URLConstructorTest extends WhatwgTestCase
{
    public function urlTestDataSuccessProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (isset($inputs['base']) && !isset($inputs['failure'])) {
                yield [(object) $inputs];
            }
        }
    }

    /**
     * @dataProvider urlTestDataSuccessProvider
     */
    public function testUrlConstructorSucceeded(stdClass $expected): void
    {
        if (property_exists($expected, 'failure')) {
            $this->expectException(TypeError::class);
            $base = $expected->base ? $expected->base : 'about:blank';
            new URL($expected->input, $base);
            return;
        }

        $base = $expected->base ? $expected->base : 'about:blank';
        $url = new URL($expected->input, $base);
        $this->assertEquals($expected->href, $url->href, 'href');
        $this->assertEquals($expected->protocol, $url->protocol, 'protocol');
        $this->assertEquals($expected->username, $url->username, 'username');
        $this->assertEquals($expected->password, $url->password, 'password');
        $this->assertEquals($expected->host, $url->host, 'host');
        $this->assertEquals($expected->hostname, $url->hostname, 'hostname');
        $this->assertEquals($expected->port, $url->port, 'port');
        $this->assertEquals($expected->pathname, $url->pathname, 'pathname');
        $this->assertEquals($expected->search, $url->search, 'search');

        if (property_exists($expected, 'searchParams')) {
            $this->assertTrue((bool) $url->searchParams);
            $this->assertEquals($expected->searchParams, $url->searchParams->toString(), 'searchParams');
        }

        $this->assertEquals($expected->hash, $url->hash, 'hash');
    }

    public function urlTestDataFailureProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (isset($inputs['failure'])) {
                yield [(object) $inputs];
            }
        }
    }

    /**
     * @dataProvider urlTestDataFailureProvider
     */
    public function testUrlConstructorFailed(stdClass $expected): void
    {
        $this->expectException(TypeError::class);
        new URL($expected->input, $expected->base);
    }

    public function test1(): void
    {
        $url = new URL('http://example.org/?a=b');
        $this->assertNotNull($url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertSame($searchParams, $url->searchParams);
    }

    /**
     * Test URL.searchParams updating, clearing.
     */
    public function test2(): void
    {
        $url = new URL('http://example.org/?a=b', 'about:blank');
        $this->assertNotNull($url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertEquals('a=b', $searchParams->toString());

        $searchParams->set('a', 'b');
        $this->assertEquals('a=b', $url->searchParams->toString());
        $this->assertEquals('?a=b', $url->search);
        $url->search = '';
        $this->assertEquals('', $url->searchParams->toString());
        $this->assertEquals('', $url->search);
        $this->assertEquals('', $searchParams->toString());
    }

    public function test3(): void
    {
        $this->expectException(TypeError::class);
        $urlString = 'http://example.org';
        $url = new URL($urlString, 'about:blank');
        $url->searchParams = new URLSearchParams($urlString);
    }

    public function test4(): void
    {
        $url = new URL('http://example.org/file?a=b&c=d');
        $this->assertInstanceOf(URLSearchParams::class, $url->searchParams);
        $searchParams = $url->searchParams;
        $this->assertEquals('?a=b&c=d', $url->search);
        $this->assertEquals('a=b&c=d', $searchParams->toString());

        // Test that setting 'search' propagates to the URL object's query
        // object
        $url->search = 'e=f&g=h';
        $this->assertEquals('?e=f&g=h', $url->search);
        $this->assertEquals('e=f&g=h', $url->searchParams->toString());

        // ...and same, but with a leading '?'
        $url->search = '?e=f&g=h';
        $this->assertEquals('?e=f&g=h', $url->search);
        $this->assertEquals('e=f&g=h', $url->searchParams->toString());

        // And in the other direction, altering searchParams propagates back
        // to 'search'
        $searchParams->append('i', ' j ');
        $this->assertEquals('?e=f&g=h&i=+j+', $url->search);
        $this->assertEquals('e=f&g=h&i=+j+', $url->searchParams->toString());
        $this->assertEquals(' j ', $searchParams->get('i'));

        $searchParams->set('e', 'updated');
        $this->assertEquals('?e=updated&g=h&i=+j+', $url->search);
        $this->assertEquals('e=updated&g=h&i=+j+', $url->searchParams->__toString());

        $url2 = new URL('http://example.org/file??a=b&c=d', 'about:blank');
        $this->assertEquals('??a=b&c=d', $url2->search);
        $this->assertEquals('%3Fa=b&c=d', $url2->searchParams->toString());

        $url2->href = 'http://example.org/file??a=b';
        $this->assertEquals('??a=b', $url2->search);
        $this->assertEquals('%3Fa=b', $url2->searchParams->toString());
    }
}
