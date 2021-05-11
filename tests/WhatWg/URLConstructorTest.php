<?php

namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use stdClass;

use function property_exists;

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
        $base = $expected->base ? $expected->base : 'about:blank';
        $url = new URL($expected->input, $base);
        $this->assertSame($expected->href, $url->href, 'href');
        $this->assertSame($expected->protocol, $url->protocol, 'protocol');
        $this->assertSame($expected->username, $url->username, 'username');
        $this->assertSame($expected->password, $url->password, 'password');
        $this->assertSame($expected->host, $url->host, 'host');
        $this->assertSame($expected->hostname, $url->hostname, 'hostname');
        $this->assertSame($expected->port, $url->port, 'port');
        $this->assertSame($expected->pathname, $url->pathname, 'pathname');
        $this->assertSame($expected->search, $url->search, 'search');

        if (property_exists($expected, 'searchParams')) {
            $this->assertTrue((bool) $url->searchParams);
            $this->assertSame($expected->searchParams, $url->searchParams->toString(), 'searchParams');
        }

        $this->assertSame($expected->hash, $url->hash, 'hash');
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
}
