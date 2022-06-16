<?php

namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URL;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/toascii.window.js
 */
class ToASCIIWindowTest extends WhatwgTestCase
{
    public function toAsciiTestProvider(): iterable
    {
        foreach ($this->loadTestData('toascii.json') as $inputs) {
            yield [$inputs];
        }
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testURLContructor(array $hostTest): void
    {
        if ($hostTest['output'] !== null) {
            $url = new URL('https://' . $hostTest['input'] . '/x');
            self::assertSame($hostTest['output'], $url->host);
            self::assertSame($hostTest['output'], $url->hostname);
            self::assertSame('/x', $url->pathname);
            self::assertSame('https://' . $hostTest['output'] . '/x', $url->href);

            return;
        }

        $this->expectException(TypeError::class);
        new URL($hostTest['input']);
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testHostSetter(array $hostTest): void
    {
        $url = new URL('https://x/x');
        $url->host = $hostTest['input'];

        self::assertSame($hostTest['output'] ?? 'x', $url->host);
    }

    /**
     * @dataProvider toAsciiTestProvider
     */
    public function testHostnameSetter(array $hostTest): void
    {
        $url = new URL('https://x/x');
        $url->hostname = $hostTest['input'];

        self::assertSame($hostTest['output'] ?? 'x', $url->hostname);
    }
}
