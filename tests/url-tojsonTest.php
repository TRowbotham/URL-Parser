<?php
namespace phpjs\tests\url;

use Rowbot\URL\URL;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/url/url-tojson.html
 */
class URLtoJsonTest extends PHPUnit_Framework_TestCase
{
    public function testBasicToJSON()
    {
        $a = new URL('https://example.com');
        $this->assertEquals('"' . str_replace('/', '\/', $a->href) . '"', json_encode($a));
        $this->assertEquals('"' . $a->href . '"', $a->toJSON());
    }
}
