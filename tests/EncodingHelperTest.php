<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Support\EncodingHelper;

class EncodingHelperTest extends TestCase
{
    public function encodingDataProvider(): array
    {
        return [
            ['REPLACEMENT', 'utf-8'],
            ['UTF-16', 'utf-8'],
            ['UTF-16LE', 'utf-8'],
            ['UTF-16BE', 'utf-8'],
            ['ASCII', 'ascii'],
        ];
    }

    /**
     * @dataProvider encodingDataProvider
     */
    public function testReplacementAndUtf16EncodingsGetForcedToUtf8($encoding, $outputEncoding): void
    {
        $this->assertSame($outputEncoding, EncodingHelper::getOutputEncoding($encoding));
    }
}
