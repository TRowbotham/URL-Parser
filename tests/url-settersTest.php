<?php
namespace phpjs\tests\url;

use phpjs\urls\exceptions\TypeError;
use phpjs\urls\URL;
use phpjs\urls\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/url/url-setters.html
 */
class URLSettersTest extends PHPUnit_Framework_TestCase
{
    protected $data;

    public function getTestData()
    {
        if (!is_array($this->data)) {
            $data = json_decode(
                file_get_contents(
                    __DIR__ . DIRECTORY_SEPARATOR . 'setters_tests.json'
                ),
                true
            );
            $this->data = [];

            foreach ($data as $setter => $testcases) {
                if ($setter === 'comment') {
                    continue;
                }

                foreach ($testcases as $testcase) {
                    $arr = $testcase;
                    if (isset($arr['comment'])) {
                        unset($arr['comment']);
                    }
                    $arr['setter'] = $setter;
                    $this->data[] = $arr;
                }
            }
        }

        return $this->data;
    }

    /**
     * @dataProvider getTestData
     */
    public function testSetters($aHref, $aNewValue, $aExpected, $aSetter)
    {
        $url = new URL($aHref);
        $url->$aSetter = $aNewValue;

        foreach ($aExpected as $attribute => $value) {
            $this->assertEquals($value, $url->$attribute, $attribute);
        }
    }
}
