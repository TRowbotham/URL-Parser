<?php
namespace phpjs\tests\url;

use Rowbot\URL\URL;
use Rowbot\URL\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/url/urlsearchparams-foreach.html
 */
class URLSearchParamsForeachTest extends PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $params = new URLSearchParams('a=1&b=2&c=3');
        $keys = [];
        $values = [];

        foreach ($params as $param) {
            $keys[] = $param[0];
            $values[] = $param[1];
        }

        $this->assertEquals(['a', 'b', 'c'], $keys);
        $this->assertEquals(['1', '2', '3'], $values);
    }

    public function test2()
    {
        $a = new URL("http://a.b/c?a=1&b=2&c=3&d=4");
        $b = $a->searchParams;
        $c = [];

        foreach ($b as $i) {
            $a->search = "x=1&y=2&z=3";
            $c[] = $i;
        }

        $this->assertEquals(["a","1"], $c[0]);
        $this->assertEquals(["y","2"], $c[1]);
        $this->assertEquals(["z","3"], $c[2]);
    }

    public function test3()
    {
        $a = new URL("http://a.b/c");
        $b = $a->searchParams;

        foreach ($b as $i) {
            // This should be unreachable.
            $this->assertTrue(false);
        }
    }
}
