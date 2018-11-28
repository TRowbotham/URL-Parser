<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/failure.html
 */
final class FailureTest extends WhatwgTestCase
{
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
}
