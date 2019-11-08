<?php
namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/failure.html
 */
class FailureTest extends WhatwgTestCase
{
    public function urlTestDataFailureProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (isset($inputs['failure']) && $inputs['base'] === 'about:blank') {
                yield [(object) $inputs];
            }
        }
    }

    /**
     * URL's constructor's first argument is tested by url-constructorTest. If a
     * URL fails to parse with any valid base, it must also fail to parse with
     * no base, i.e. when used as a base URL itself.
     *
     * @dataProvider urlTestDataFailureProvider
     */
    public function testURLContructor(stdClass $test): void
    {
        $this->expectException(TypeError::class);
        new URL("about:blank", $test->input);
    }

    /**
     * @dataProvider urlTestDataFailureProvider
     */
    public function testUrlHrefSetterThrows(stdClass $test): void
    {
        $this->expectException(TypeError::class);
        $url = new URL('about:blank');
        $url->href = $test->input;
    }
}
