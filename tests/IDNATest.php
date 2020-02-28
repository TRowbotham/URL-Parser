<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Component\Host\Exception\IDNATransformException;
use Rowbot\URL\Component\Host\IDNA;

use function implode;
use function str_repeat;
use function str_split;

class IDNATest extends TestCase
{
    /**
     * Tests PHP bug #72506
     */
    public function testToAsciiFailsOnStringsLargerThan253Bytes(): void
    {
        $bytes = 254;
        $labelLength = 63;
        $string = str_repeat('a', $bytes);
        $domain = implode('.', str_split($string, $labelLength));

        $this->expectException(IDNATransformException::class);
        IDNA::toAscii($domain);
    }

    public function testToAsciiEmptyDomainWithoutDomainLengthValidationDoesNotThrow(): void
    {
        $this->assertEmpty(IDNA::toAscii(''));
    }
}
