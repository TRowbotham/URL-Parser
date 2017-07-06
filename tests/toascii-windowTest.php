<?php
namespace phpjs\tests\urls;

use phpjs\urls\exception\TypeError;
use phpjs\urls\URL;
use PHPUnit_Framework_TestCase;

class toAsciiTest extends PHPUnit_Framework_TestCase
{
    protected $testData = null;

    public function getTestData()
    {
        if (!isset($this->testData)) {
            $data = json_decode(
                file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'toascii.json'
                )
            );

            $this->testData = [];

            foreach ($data as $d) {
                $this->testData[] = [$d];
            }
        }

        return $this->testData;
    }

    /**
     * @dataProvider getTestData
     */
    public function test($test)
    {
        if (is_string($test)) {
            return;
        }

        if ($test->output !== null) {
            $url = new URL('https://' . $test->input . '/x');
            $this->assertEquals($test->output, $url->host);
            $this->assertEquals($test->output, $url->hostname);
            $this->assertEquals('/x', $url->pathname);
            $this->assertEquals('https://' . $test->output . '/x', $url->href);
        } else {
            $this->expectException(TypeError::class);
            $url = new URL($test->input);
        }
    }
}
