<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests\WhatWg;

use Rowbot\URL\BasicURLParser;
use Rowbot\URL\String\EncodeSet;
use Rowbot\URL\String\PercentEncodeTrait;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\Tests\WhatWg\WhatwgTestCase;

class PercentEncodingTest extends WhatwgTestCase
{
    use PercentEncodeTrait;

    /**
     * @dataProvider percentEncodedDataProvider
     */
    public function testPercentEncoding(string $input, array $output): void
    {
        $parser = new BasicURLParser();
        $in = new Utf8String("https://doesnotmatter.invalid/?{$input}#{$input}");

        foreach ($output as $encoding => $expected) {
            $url = $parser->parse($in, null, $encoding);

            self::assertNotFalse($url);
            self::assertSame($expected, $url->query, $encoding);
            self::assertSame($output['utf-8'], $url->fragment);
        }
    }

    public static function percentEncodedDataProvider(): iterable
    {
        return self::loadTestData('percent-encoding.json');
    }

    /**
     * @see https://url.spec.whatwg.org/#example-percent-encode-operations
     *
     * @dataProvider exampleDataProvider
     */
    public function testPercentEncodingExamples(string $encoding, string $input, string $output, int $encodeSet, bool $spaceAsPlus): void
    {
        $result = $this->percentEncodeAfterEncoding($encoding, $input, $encodeSet, $spaceAsPlus);
        self::assertSame($output, $result);
    }

    public static function exampleDataProvider(): array
    {
        return [
            ['encoding' => 'Shift_JIS', 'input' => ' ', 'output' => '%20', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
            ['encoding' => 'Shift_JIS', 'input' => '≡', 'output' => '%81%DF', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
            ['encoding' => 'Shift_JIS', 'input' => '‽', 'output' => '%26%238253%3B', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
            ['encoding' => 'ISO-2022-JP', 'input' => '¥', 'output' => '%1B(J\%1B(B', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
            ['encoding' => 'Shift_JIS', 'input' => '1+1 ≡ 2%20‽', 'output' => '1+1+%81%DF+2%20%26%238253%3B', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => true],
            ['encoding' => 'UTF-8', 'input' => '≡', 'ouput' => '%E2%89%A1', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
            ['encoding' => 'UTF-8', 'input' => '‽', 'output' => '%E2%80%BD', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
            ['encoding' => 'UTF-8', 'input' => 'Say what‽', 'output' => 'Say%20what%E2%80%BD', 'encode_set' => EncodeSet::USERINFO, 'spaceAsPlus' => false],
        ];
    }
}
