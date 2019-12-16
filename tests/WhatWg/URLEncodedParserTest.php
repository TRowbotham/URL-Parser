<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\URLSearchParams;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlencoded-parser.any.js
 */
class URLEncodedParserTest extends TestCase
{
    public function getTestData(): array
    {
        return [
            ['input' => 'test', 'output' => [['test', '']]],
            ['input' => "\u{FEFF}test=\u{FEFF}", 'output' => [["\u{FEFF}test", "\u{FEFF}"]]],
            ['input' => '%EF%BB%BFtest=%EF%BB%BF', 'output' => [["\u{FEFF}test", "\u{FEFF}"]]],
            ['input' => '%FE%FF', 'output' => [["\u{FFFD}\u{FFFD}", '']]],
            ['input' => '†&†=x', 'output' => [['†', ''], ['†', 'x']]],
            ['input' => '%C2', 'output' => [["\u{FFFD}", '']]],
            ['input' => '%C2x', 'output' => [["\u{FFFD}x", '']]],
            [
                'input' => '_charset_=windows-1252&test=%C2x',
                'output' => [['_charset_', 'windows-1252'], ['test', "\u{FFFD}x"]],
            ],
            ['input' => '', 'output' => []],
            ['input' => 'a', 'output' => [['a', '']]],
            ['input' => 'a=b', 'output' => [['a', 'b']]],
            ['input' => 'a=', 'output' => [['a', '']]],
            ['input' => '=b', 'output' => [['', 'b']]],
            ['input' => '&', 'output' => []],
            ['input' => '&a', 'output' => [['a', '']]],
            ['input' => 'a&', 'output' => [['a', '']]],
            ['input' => 'a&a', 'output' => [['a', ''], ['a', '']]],
            ['input' => 'a&b&c', 'output' => [['a', ''], ['b', ''], ['c', '']]],
            ['input' => 'a=b&c=d', 'output' => [['a', 'b'], ['c', 'd']]],
            ['input' => 'a=b&c=d&', 'output' => [['a', 'b'], ['c', 'd']]],
            ['input' => '&&&a=b&&&&c=d&', 'output' => [['a', 'b'], ['c', 'd']]],
            ['input' => 'a=a&a=b&a=c', 'output' => [['a', 'a'], ['a', 'b'], ['a', 'c']]],
            ['input' => 'a==a', 'output' => [['a', '=a']]],
            ['input' => 'a=a+b+c+d', 'output' => [['a', 'a b c d']]],
            ['input' => '%=a', 'output' => [['%', 'a']]],
            ['input' => '%a=a', 'output' => [['%a', 'a']]],
            ['input' => '%a_=a', 'output' => [['%a_', 'a']]],
            ['input' => '%61=a', 'output' => [['a', 'a']]],
            ['input' => '%61+%4d%4D=', 'output' => [['a MM', '']]],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function test(string $input, array $output): void
    {
        $sp = new URLSearchParams($input);
        $i = 0;

        if (in_array($input, ['', '&'], true)) {
            $this->assertFalse($sp->valid());

            return;
        }

        foreach ($sp as $item) {
            $this->assertEquals($output[$i++], $item);
        }
    }
}
