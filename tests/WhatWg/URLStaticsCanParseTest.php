<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URL;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-statics-canparse.any.js
 */
class URLStaticsCanParseTest extends TestCase
{
    /**
     * @dataProvider inputProvider
     */
    public function testCanParse(string $url, ?string $base, bool $expected): void
    {
        self::assertSame($expected, URL::canParse($url, $base));
    }

    public static function inputProvider(): array
    {
        return [
            ['url' => 'a:b', 'base' => null, 'expected' => true],
            ['url' => 'a:/b', 'base' => null, 'expected' => true],
            ['url' => 'https://test:test', 'base' => null, 'expected' => false],
            ['url' => 'a', 'base' => 'https://b/', 'expected' => true],
        ];
    }
}
