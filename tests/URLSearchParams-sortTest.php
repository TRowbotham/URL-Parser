<?php
namespace phpjs\tests\url;

use phpjs\urls\URL;
use phpjs\urls\URLSearchParams;
use PHPUnit_Framework_TestCase;

/**
 * @see https://github.com/w3c/web-platform-tests/blob/master/url/urlsearchparams-sort.html
 */
class URLSearchParamsSortTest extends PHPUnit_Framework_TestCase
{
    public function getTestData()
    {
        return [
            [
                'input' => 'z=b&a=b&z=a&a=a',
                'output' => [
                    ['a', 'b'],
                    ['a', 'a'],
                    ['z', 'b'],
                    ['z', 'a']
                ]
            ],
            [
                'input' => "\u{FFFD}=x&\u{FFFC}&\u{FFFD}=a",
                'output' => [
                    ["\u{FFFC}", ''],
                    ["\u{FFFD}", 'x'],
                    ["\u{FFFD}", 'a']
                ]
            ],
            [
                'input' => 'ﬃ&🌈',
                'output' => [["🌈", ""], ["ﬃ", ""]]
            ],
            [
                'input' => "é&e\u{FFFD}&e\u{0301}",
                'output' => [
                    ["e\u{0301}", ""],
                    ["e\u{FFFD}", ""],
                    ["é", ""]
                ]
            ],
            [
                "input" => "z=z&a=a&z=y&a=b&z=x&a=c&z=w&a=d&z=v&a=e&z=u&a=f&z=t&a=g",
                "output" => [
                    ["a", "a"],
                    ["a", "b"],
                    ["a", "c"],
                    ["a", "d"],
                    ["a", "e"],
                    ["a", "f"],
                    ["a", "g"],
                    ["z", "z"],
                    ["z", "y"],
                    ["z", "x"],
                    ["z", "w"],
                    ["z", "v"],
                    ["z", "u"],
                    ["z", "t"]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testSort($input, $output)
    {
        $url = new URL('?' . $input, 'https://example/');
        $url->searchParams->sort();

        $params = new URLSearchParams($url->search);
        $i = 0;

        foreach ($params as $param) {
            $this->assertEquals($output[$i], $param);
            $i++;
        }
    }
}
