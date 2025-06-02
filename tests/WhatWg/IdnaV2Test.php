<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\Attributes\DataProvider;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/IdnaTestV2.window.js
 */
class IdnaV2Test extends WhatwgTestCase
{
    public static function idnaTestProvider(): iterable
    {
        foreach (self::loadTestData('IdnaTestV2.json') as $inputs) {
            if ($inputs['input'] !== '') {
                yield [$inputs];
            }
        }
    }

    #[DataProvider('idnaTestProvider')]
    public function testIdna($idnaTest)
    {
        if ($idnaTest['output'] === null) {
            $this->expectException(TypeError::class);
        }

        $url = new URL("https://{$idnaTest['input']}/x");
        self::assertSame($idnaTest['output'], $url->host);
        self::assertSame($idnaTest['output'], $url->hostname);
        self::assertSame('/x', $url->pathname);
        self::assertSame("https://{$idnaTest['output']}/x", $url->href);
    }
}
