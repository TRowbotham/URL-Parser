<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Component\Path;

class PathTest extends TestCase
{
    public function isNormalizedWindowsDriveLetterProvider(): array
    {
        return [
            ['c:', true],
            ['c:/', false],
            ['c:a', false],
            ['4:', false],
            ['az:', false],
            ['a|', false],
            ['a:|', false],
            ['', false],
            ['c:\\', false],
            ['c:?', false],
            ['c:#', false],
            ['c:/f', false],
        ];
    }

    /**
     * @dataProvider isNormalizedWindowsDriveLetterProvider
     */
    public function testIsNormalizedWindowsDriveLetter(string $input, bool $expected): void
    {
        $s = new Path($input);
        self::assertSame($expected, $s->isNormalizedWindowsDriveLetter());
    }
}
