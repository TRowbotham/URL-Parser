<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\Attributes\DataProvider;
use Rowbot\URL\URL;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-statics-parse.any.js
 */
class URLStaticsParseTest extends WhatwgTestCase
{
    #[DataProvider('parseDataProvider')]
    public function testParse(?string $url, ?string $base, bool $expected): void
    {
        if (!$expected) {
            self::assertNull(URL::parse($url, $base));

            return;
        }

        self::assertSame((new URL($url, $base))->href, URL::parse($url, $base)->href);
    }

    public function testParseShouldReturnUniqueObject(): void
    {
        self::assertNotSame(URL::parse('https://example.com/'), URL::parse('https://example.com/'));
    }

    public static function parseDataProvider(): iterable
    {
        return [
            [
                "url" => '',
                "base" => null,
                "expected" => false
            ],
            [
                "url" => "aaa:b",
                "base" => null,
                "expected" => true
            ],
            [
                "url" => '',
                "base" => "aaa:b",
                "expected" => false
            ],
            [
                "url" => "aaa:/b",
                "base" => null,
                "expected" => true
            ],
            [
                "url" => '',
                "base" => "aaa:/b",
                "expected" => true
            ],
            [
                "url" => "https://test:test",
                "base" => null,
                "expected" => false
            ],
            [
                "url" => "a",
                "base" => "https://b/",
                "expected" => true
            ],
        ];
    }
}
