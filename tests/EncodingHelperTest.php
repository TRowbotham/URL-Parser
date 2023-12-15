<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Rowbot\URL\Support\EncodingHelper;

class EncodingHelperTest extends TestCase
{
    #[TestWith(['REPLACEMENT', 'utf-8'])]
    #[TestWith(['UTF-16', 'utf-8'])]
    #[TestWith(['UTF-16LE', 'utf-8'])]
    #[TestWith(['UTF-16BE', 'utf-8'])]
    #[TestWith(['ASCII', 'ascii'])]
    public function testReplacementAndUtf16EncodingsGetForcedToUtf8(string $encoding, string $outputEncoding): void
    {
        self::assertSame($outputEncoding, EncodingHelper::getOutputEncoding($encoding));
    }
}
