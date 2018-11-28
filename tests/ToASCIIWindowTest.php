<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/toascii.window.js
 */
class ToASCIIWindowTest extends WhatwgTestCase
{
    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testURLContructor(stdClass $hostTest): void
    {
        if ($hostTest->output !== null) {
            $url = new URL('https://' . $hostTest->input . '/x');
            $this->assertEquals($hostTest->output, $url->host);
            $this->assertEquals($hostTest->output, $url->hostname);
            $this->assertEquals('/x', $url->pathname);
            $this->assertEquals(
                'https://' . $hostTest->output . '/x',
                $url->href
            );
            return;
        }

        $this->expectException(TypeError::class);
        new URL($hostTest->input);
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testHostSetter($hostTest)
    {
        $url = new URL('https://x/x');
        $url->host = $hostTest->input;
        if ($hostTest->output !== null) {
            $this->assertEquals($hostTest->output, $url->host);
        } else {
            $this->assertEquals('x', $url->host);
        }
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testHostnameSetter($hostTest)
    {
        // Skip comments
        if (is_string($hostTest)) {
            return;
        }

        $url = new URL('https://x/x');
        $url->hostname = $hostTest->input;

        if ($hostTest->output !== null) {
            $this->assertEquals($hostTest->output, $url->hostname);
        } else {
            $this->assertEquals('x', $url->hostname);
        }
    }
}
