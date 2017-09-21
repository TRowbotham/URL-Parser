<?php
namespace phpjs\tests\url;

use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/url/urlsearchparams-delete.html
 */
class URLSearchParamsDeleteTest extends PHPUnit_Framework_TestCase
{
    public function testDeleteBasics()
    {
        $params = new URLSearchParams('a=b&c=d');
        $params->delete('a');
        $this->assertEquals('c=d', $params . '');
        $params = new URLSearchParams('a=a&b=b&a=a&c=c');
        $params->delete('a');
        $this->assertEquals('b=b&c=c', $params . '');
        $params = new URLSearchParams('a=a&=&b=b&c=c');
        $params->delete('');
        $this->assertEquals('a=a&b=b&c=c', $params . '');
        $params = new URLSearchParams('a=a&null=null&b=b');
        $params->delete(null);
        $this->assertEquals('a=a&b=b', $params . '');
    }

    public function testDeleteAppendMultiple()
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        $this->assertTrue($params->has('first'));
        $this->assertEquals('1', $params->get('first'));
        $params->delete('first');
        $this->assertFalse($params->has('first'));
        $params->append('first', 1);
        $params->append('first', 10);
        $params->delete('first');
        $this->assertFalse($params->has('first'));
    }
}
