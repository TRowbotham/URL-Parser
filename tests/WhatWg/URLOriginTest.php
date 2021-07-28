<?php

namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\URL;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-origin.html
 */
class URLOriginTest extends WhatwgTestCase
{
    public function urlTestDataOriginProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (isset($inputs['origin'])) {
                yield [(object) $inputs];
            }
        }
    }

    /**
     * @dataProvider urlTestDataOriginProvider
     */
    public function testOrigin(stdClass $expected): void
    {
        $url = isset($expected->base) ? new URL($expected->input, $expected->base) : new URL($expected->input);
        self::assertSame($expected->origin, $url->origin);
    }
}
