<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Rowbot\URL\BasicURLParser;
use Rowbot\URL\String\Utf8String;

class URLRecordTest extends TestCase
{
    public function testUnknownBlobUrlCreatesOpaqueOrigin(): void
    {
        $parser = new BasicURLParser();
        $record = $parser->parse(new Utf8String('blob:failure'));
        $origin = $record->getOrigin();
        self::assertTrue($origin->isOpaque());
        self::assertNull($origin->getEffectiveDomain());
    }

    public function testFileSchemeCreatesOpaqueOrigin(): void
    {
        $parser = new BasicURLParser();
        $record = $parser->parse(new Utf8String('file:///C:/Users/Desktop/'));
        $origin = $record->getOrigin();
        self::assertTrue($origin->isOpaque());
        self::assertNull($origin->getEffectiveDomain());
    }

    #[TestWith(['file:///C:/Users/Desktop/', 'file:///C|/Users/Desktop/', true, true])]
    #[TestWith(['https://example.com/path/?query#foo', 'https://example.com/path/?query', false, true])]
    #[TestWith(['http://example.com/path/foo/#bar', 'http://example.com/bar/../path/foo/#bar', true, true])]
    public function testEquality(
        string $urlA,
        string $urlB,
        bool $isEqualWithHash,
        bool $isEqualWithoutHash
    ): void {
        $parser = new BasicURLParser();
        $recordA = $parser->parse(new Utf8String($urlA));
        $recordB = $parser->parse(new Utf8String($urlB));

        self::assertSame($isEqualWithHash, $recordA->isEqual($recordB, false));
        self::assertSame($isEqualWithoutHash, $recordA->isEqual($recordB, true));
    }
}
