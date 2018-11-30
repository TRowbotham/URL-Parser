<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-setters.html
 */
class URLSettersTest extends WhatwgTestCase
{
    public function urlSetterGetterDataProvider(): iterable
    {
        foreach ($this->loadTestData('setters_tests.json') as $key => $tests) {
            if ('comment' === $key) {
                continue;
            }

            foreach ($tests as $inputs) {
                unset($inputs['comment']);
                $inputs['setter'] = $key;

                yield [(object) $inputs];
            }
        }
    }

    /**
     * @dataProvider urlSetterGetterDataProvider
     */
    public function testSetters(stdClass $input): void
    {
        $url = new URL($input->href);
        $url->{$input->setter} = $input->new_value;

        foreach ($input->expected as $attribute => $value) {
            $this->assertEquals($value, $url->$attribute, $attribute);
        }
    }
}
