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
        self::assertSame($expected->href, $url->href, 'href');
        self::assertSame($expected->protocol, $url->protocol, 'protocol');
        self::assertSame($expected->username, $url->username, 'username');
        self::assertSame($expected->password, $url->password, 'password');
        self::assertSame($expected->host, $url->host, 'host');
        self::assertSame($expected->hostname, $url->hostname, 'hostname');
        self::assertSame($expected->port, $url->port, 'port');
        self::assertSame($expected->pathname, $url->pathname, 'pathname');
        self::assertSame($expected->search, $url->search, 'search');

        if (property_exists($expected, 'searchParams')) {
            self::assertTrue((bool) $url->searchParams);
            self::assertSame($expected->searchParams, $url->searchParams->toString(), 'searchParams');
        }

        self::assertSame($expected->hash, $url->hash, 'hash');
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
