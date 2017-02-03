<?php
namespace phpjs\tests\url;

use phpjs\urls\Origin;
use PHPUnit_Framework_TestCase;

class OriginTest extends PHPUnit_Framework_TestCase
{
    public function testOrigin()
    {
        $o1 = new Origin('https', 'example.org', null, null);
        $o2 = new Origin('https', 'example.org', 314, 'example.org');
        $o3 = new Origin('https', 'example.org', 420, 'example.org');
        $o4 = new Origin('https', 'example.org', null, 'example.org');
        $o5 = new Origin('http', 'example.org', null, 'example.org');

        $this->assertTrue($o1->isSameOrigin($o1));
        $this->assertTrue($o1->isSameOriginDomain($o1));
        $this->assertFalse($o2->isSameOrigin($o3));
        $this->assertTrue($o2->isSameOriginDomain($o3));
        $this->assertTrue($o1->isSameOrigin($o4));
        $this->assertFalse($o1->isSameOriginDomain($o4));
        $this->assertFalse($o4->isSameOrigin($o5));
        $this->assertFalse($o4->isSameOriginDomain($o5));
    }

    public function testOriginUnicodeSerialization()
    {
        $o = new Origin('https', 'xn--maraa-rta.example', null, null);
        $this->assertEquals('https://maraña.example', $o->serializeAsUnicode(), 'message');
    }

    public function testOriginASCIISerialization()
    {
        $o = new Origin('https', 'xn--maraa-rta.example', null, null);
        $this->assertEquals('https://xn--maraa-rta.example', $o->serializeAsASCII(), 'message');
    }
}
