<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Component\Host\NullHost;
use Rowbot\URL\Component\Host\StringHost;

class NullHostTest extends TestCase
{
    public function testNullHostSerializesToEmptyString(): void
    {
        $host = new NullHost();
        $serializer = $host->getSerializer();
        $this->assertEmpty($host->getSerializer()->toFormattedString());
        $this->assertEmpty($host->getSerializer()->toString());
    }

    public function testNullHostIsEqualOnlyToItself(): void
    {
        $host = new NullHost();
        $this->assertTrue($host->equals($host));
        $this->assertTrue($host->equals(new NullHost()));
        $this->assertFalse($host->equals(new StringHost()));
    }
}
