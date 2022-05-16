<?php

namespace Rowbot\URL\Tests\WhatWg;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use function array_filter;
use function hexdec;
use function json_decode;
use function json_encode;
use function preg_match;
use function substr_replace;

use const JSON_THROW_ON_ERROR;
use const PREG_OFFSET_CAPTURE;

abstract class WhatwgTestCase extends TestCase
{
    private const WHATWG_BASE_URI = 'https://raw.githubusercontent.com/web-platform-tests/wpt/master/url/resources/';
    private const CACHE_TTL = 86400 * 7; // 7 DAYS
    private const JSON_DEPTH = 512;

    protected function loadTestData(string $url): array
    {
        static $client;

        $cache = new FilesystemAdapter('whatwg-test-suite', self::CACHE_TTL, __DIR__ . '/data');
        $data = $cache->get($url, static function () use (&$client, $url): string {
            $client ??= new Client([
                'base_uri'    => self::WHATWG_BASE_URI,
                'http_errors' => true,
            ]);
            $body = (string) $client->get($url)->getBody();
            $offset = 0;

            // Replace all unpaired surrogate escape sequences with a \uFFFD escape sequence to avoid
            // json_decode() having a stroke and emitting a JSON_ERROR_UTF16 error causing the decode
            // to fail
            while (preg_match('/\\\u([[:xdigit:]]{4})/', $body, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
                $codePoint1 = hexdec($matches[1][0]);
                $offset = $matches[0][1] + 6;

                if ($codePoint1 >= 0xD800 && $codePoint1 <= 0xDBFF) {
                    // There is no following code point, so replace it with a \uFFFD
                    if (
                        preg_match(
                            '/\G\\\u([[:xdigit:]]{4})/',
                            $body,
                            $m,
                            PREG_OFFSET_CAPTURE,
                            $matches[1][1] + 4
                        ) !== 1
                    ) {
                        $body = substr_replace($body, '\\uFFFD', $matches[0][1], 6);

                        continue;
                    }

                    $codePoint2 = hexdec($m[1][0]);

                    // If next code point is not a low surrogate, replace it with a \uFFFD
                    if ($codePoint2 < 0xDC00 || $codePoint2 > 0xDFFF) {
                        $body = substr_replace($body, '\\uFFFD', $matches[0][1], 6);

                        continue;
                    }

                    $offset += 6;
                } elseif ($codePoint1 >= 0xDC00 && $codePoint1 <= 0xDFFF) {
                    // lone low surrogate, replace it with a \uFFFD
                    $body = substr_replace($body, '\\uFFFD', $matches[0][1], 6);
                }
            }

            // Remove comments and check to make sure it is valid JSON.
            $json = array_filter(json_decode($body, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR), 'is_array');

            return json_encode($json, JSON_THROW_ON_ERROR);
        });

        return json_decode($data, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
    }
}
