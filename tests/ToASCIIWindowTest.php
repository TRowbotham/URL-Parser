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
    public function toAsciiTestProvider(): iterable
    {
        foreach ($this->loadTestData('toascii.json') as $inputs) {
            yield [(object) $inputs];
        }
    }

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
            $this->assertEquals('https://' . $hostTest->output . '/x', $url->href);
            return;
        }

        $this->expectException(TypeError::class);
        $url = new URL($hostTest->input);
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testHostSetter(stdClass $hostTest): void
    {
        $url = new URL('https://x/x');
        $url->host = $hostTest->input;

        $this->assertEquals($hostTest->output ?? 'x', $url->host);
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testHostnameSetter(stdClass $hostTest): void
    {
        $url = new URL('https://x/x');
        $url->hostname = $hostTest->input;

        $this->assertEquals($hostTest->output ?? 'x', $url->hostname);
    }
}
