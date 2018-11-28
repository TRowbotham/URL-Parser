<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\URL;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-origin.html
 */
class URLOriginTest extends WhatwgTestCase
{
    /**
     * @dataProvider urlTestDataOriginProvider
     */
    public function testOrigin(stdClass $expected): void
    {
        $base = $expected->base ? $expected->base : 'about:blank';
        $url = new URL($expected->input, $base);
        $this->assertEquals($expected->origin, $url->origin);
    }
}
