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
        self::assertEmpty($host->getSerializer()->toFormattedString());
        self::assertEmpty($host->getSerializer()->toString());
    }

    public function testNullHostIsEqualOnlyToItself(): void
    {
        $host = new NullHost();
        self::assertTrue($host->equals($host));
        self::assertTrue($host->equals(new NullHost()));
        self::assertFalse($host->equals(new StringHost()));
    }
}
