<?php
namespace Rowbot\URL\Tests;

use Rowbot\URL\URL;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-tojson.html
 */
class URLToJSONTest extends TestCase
{
    public function testBasicToJSON(): void
    {
        $a = new URL('https://example.com');
        $this->assertEquals('"' . str_replace('/', '\/', $a->href) . '"', json_encode($a));
        $this->assertEquals('"' . $a->href . '"', $a->toJSON());
    }
}
