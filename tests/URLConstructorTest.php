<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
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
}
