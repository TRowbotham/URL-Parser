<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Rowbot\URL\URL;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/url-statics-canparse.any.js
 */
class URLStaticsCanParseTest extends TestCase
{
    #[TestWith(['url' => 'a:b', 'base' => null, 'expected' => true])]
    #[TestWith(['url' => 'a:/b', 'base' => null, 'expected' => true])]
    #[TestWith(['url' => 'undefined', 'base' => 'aaa:b', 'expected' => false])]
    #[TestWith(['url' => 'undefined', 'base' => 'https://test:test/', 'expected' => false])]
    #[TestWith(['url' => 'https://test:test', 'base' => null, 'expected' => false])]
    #[TestWith(['url' => 'a', 'base' => 'https://b/', 'expected' => true])]
    public function testCanParse(string $url, ?string $base, bool $expected): void
    {
        self::assertSame($expected, URL::canParse($url, $base));
    }
}
