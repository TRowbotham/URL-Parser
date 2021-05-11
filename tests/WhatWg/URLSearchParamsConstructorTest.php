<?php

namespace Rowbot\URL\Tests\WhatWg;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\URLSearchParams;
use stdClass;

/**
 * @see https://github.com/web-platform-tests/wpt/blob/master/url/urlsearchparams-constructor.any.js
 */
class URLSearchParamsConstructorTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $params = new URLSearchParams();
        $this->assertSame('', $params . '');
        $params = new URLSearchParams('');
        $this->assertSame('', $params . '');
        $params = new URLSearchParams('a=b');
        $this->assertSame('a=b', $params . '');
        $params = new URLSearchParams($params);
        $this->assertSame('a=b', $params . '');
    }

    public function testConstructorNoArguments(): void
    {
        $params = new URLSearchParams();
        $this->assertSame('', $params->toString());
    }

    public function testRemovingLeadingQuestionMark(): void
    {
        $params = new URLSearchParams('?a=b');
        $this->assertSame('a=b', $params->toString());
    }

    public function testConstructorEmptyObject(): void
    {
        $params = new URLSearchParams(new stdClass());
        $this->assertSame('', (string) $params);
    }

    public function testConstructorString(): void
    {
        $params = new URLSearchParams('a=b');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('a'));
        $this->assertFalse($params->has('b'));
        $params = new URLSearchParams('a=b&c');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('a'));
        $this->assertTrue($params->has('c'));
        $params = new URLSearchParams('&a&&& &&&&&a+b=& c&m%c3%b8%c3%b8');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('a'));
        $this->assertTrue($params->has('a b'));
        $this->assertTrue($params->has(' '));
        $this->assertFalse($params->has('c'));
        $this->assertTrue($params->has(' c'));
        $this->assertTrue($params->has('møø'));

        $params = new URLSearchParams('id=0&value=%');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('id'));
        $this->assertTrue($params->has('value'));
        $this->assertSame('0', $params->get('id'));
        $this->assertSame('%', $params->get('value'));

        $params = new URLSearchParams('b=%2sf%2a');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('b'));
        $this->assertSame('%2sf*', $params->get('b'));

        $params = new URLSearchParams('b=%2%2af%2a');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('b'));
        $this->assertSame('%2*f*', $params->get('b'));

        $params = new URLSearchParams('b=%%2a');
        $this->assertNotNull($params);
        $this->assertTrue($params->has('b'));
        $this->assertSame('%*', $params->get('b'));
    }

    public function testConstructorObject(): void
    {
        $seed = new URLSearchParams('a=b&c=d');
        $params = new URLSearchParams($seed);
        $this->assertNotNull($params, 'message');
        $this->assertSame('b', $params->get('a'));
        $this->assertSame('d', $params->get('c'));
        $this->assertFalse($params->has('d'), 'message');
        // The name-value pairs are copied when  created; later, updates should
        // not be observable.
        $seed->append('e', 'f');
        $this->assertFalse($params->has('e'));
        $params->append('g', 'h');
        $this->assertFalse($seed->has('g'));
    }

    public function testParsePlusSign(): void
    {
        $params = new URLSearchParams('a=b+c');
        $this->assertSame('b c', $params->get('a'));
        $params = new URLSearchParams('a+b=c');
        $this->assertSame('c', $params->get('a b'));
    }

    public function testParsePlusSignPercentEncoded(): void
    {
        $testValue = '+15555555555';
        $params = new URLSearchParams();
        $params->set('query', $testValue);

        $newParams = new URLSearchParams($params->toString());
        $this->assertSame('query=%2B15555555555', $params->toString());
        $this->assertSame($testValue, $params->get('query'));
        $this->assertSame($testValue, $newParams->get('query'));
    }

    public function testParseSpace(): void
    {
        $params = new URLSearchParams('a=b c');
        $this->assertSame('b c', $params->get('a'));
        $params = new URLSearchParams('a b=c');
        $this->assertSame('c', $params->get('a b'));
    }

    public function testParseSpacePercentEncoded(): void
    {
        $params = new URLSearchParams('a=b%20c');
        $this->assertSame('b c', $params->get('a'));
        $params = new URLSearchParams('a%20b=c');
        $this->assertSame('c', $params->get('a b'));
    }

    public function testParseNullByte(): void
    {
        $params = new URLSearchParams("a=b\0c");
        $this->assertSame("b\0c", $params->get('a'));
        $params = new URLSearchParams("a\0b=c");
        $this->assertSame('c', $params->get("a\0b"));
    }

    public function testParseNullBytePercentEncoded(): void
    {
        $params = new URLSearchParams('a=b%00c');
        $this->assertSame("b\0c", $params->get('a'));
        $params = new URLSearchParams('a%00b=c');
        $this->assertSame('c', $params->get("a\0b"));
    }

    public function testParseUnicodeCompositionSymbol(): void
    {
        $params = new URLSearchParams("a=b\u{2384}");
        $this->assertSame("b\u{2384}", $params->get('a'));
        $params = new URLSearchParams("a\u{2384}=c");
        $this->assertSame('c', $params->get("a\u{2384}"));
    }

    public function testParseUnicodeCompositionSymbolPercentEncoded(): void
    {
        $params = new URLSearchParams('a=b%E2%8E%84');
        $this->assertSame("b\u{2384}", $params->get('a'));
        $params = new URLSearchParams('a%E2%8E%84=c');
        $this->assertSame('c', $params->get("a\u{2384}"));
    }

    public function testParseUnicodePileOfPoo(): void
    {
        $params = new URLSearchParams("a=b\u{1F4A9}c");
        $this->assertSame("b\u{1F4A9}c", $params->get('a'));
        $params = new URLSearchParams("a\u{1F4A9}b=c");
        $this->assertSame('c', $params->get("a\u{1F4A9}b"));
    }

    public function testParseUnicodePileOfPooPercentEncoded(): void
    {
        $params = new URLSearchParams('a=b%f0%9f%92%a9c');
        $this->assertSame("b\u{1F4A9}c", $params->get('a'));
        $params = new URLSearchParams('a%f0%9f%92%a9b=c');
        $this->assertSame('c', $params->get("a\u{1F4A9}b"));
    }

    public function testSequenceOfSequences(): void
    {
        $params = new URLSearchParams([]);
        $this->assertNotNull($params);
        $params = new URLSearchParams([['a', 'b'], ['c', 'd']]);
        $this->assertSame('b', $params->get('a'));
        $this->assertSame('d', $params->get('c'));

        try {
            new URLSearchParams([[1]]);
            $this->assertTrue(false);
        } catch (TypeError $e) {
            $this->assertTrue(true);
        }

        try {
            new URLSearchParams([[1, 2, 3]]);
            $this->assertTrue(false);
        } catch (TypeError $e) {
            $this->assertTrue(true);
        }
    }

    public function getTestData(): array
    {
        $obj = new stdClass();
        $obj->{'+'} = '%C2';

        $obj2 = new stdClass();
        $obj2->c = 'x';
        $obj2->a = '?';

        $obj3 = new stdClass();
        $obj3->{"a\0b"} = '42';
        $obj3->{"c\u{D83D}"} = '23';
        $obj3->{"d\u{1234}"} = 'foo';

        return [
            ['input' => $obj, 'output' => [['+', '%C2']]],
            [
                'input' => $obj2,
                'output' => [
                    ['c', 'x'],
                    ['a', '?'],
                ],
            ],
            [
                'input' => [
                    ['c', 'x'],
                    ['a', '?'],
                ],
                'output' => [
                    ['c', 'x'],
                    ['a', '?'],
                ],
            ],
            [
                'input' => $obj3,
                'output' => [
                    ["a\0b", '42'],
                    ["c\u{FFFD}", '23'],
                    ["d\u{1234}", 'foo'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function test($input, array $output): void
    {
        $params = new URLSearchParams($input);
        $i = 0;

        foreach ($params as $param) {
            $this->assertSame($output[$i++], $param);
        }
    }
}
