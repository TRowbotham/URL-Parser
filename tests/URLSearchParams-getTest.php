<?php
namespace phpjs\tests\urls;

use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/url/urlsearchparams-get.html
 */
class URLSearchParamsGetTest extends PHPUnit_Framework_TestCase
{
    public function testGetBasics()
    {
        $params = new URLSearchParams('a=b&c=d');
        $this->assertEquals('b', $params->get('a'));
        $this->assertEquals('d', $params->get('c'));
        $this->assertNull($params->get('e'));
        $params = new URLSearchParams('a=b&c=d&a=e');
        $this->assertEquals('b', $params->get('a'));
        $params = new URLSearchParams('=b&c=d');
        $this->assertEquals('b', $params->get(''));
        $params = new URLSearchParams('a=&c=d&a=e');
        $this->assertEquals('', $params->get('a'));
        $params = new URLSearchParams('first=second&third&&');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('first'));
        $this->assertEquals('second', $params->get('first'));
        $this->assertEquals('', $params->get('third'));
        $this->assertNull($params->get('fourth'));
    }
}
