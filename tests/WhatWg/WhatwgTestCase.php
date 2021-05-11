<?php

namespace Rowbot\URL\Tests\WhatWg;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use function array_filter;
use function GuzzleHttp\Psr7\uri_for;
use function hexdec;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function sprintf;
use function substr_replace;

use const JSON_ERROR_NONE;
use const PREG_OFFSET_CAPTURE;

abstract class WhatwgTestCase extends TestCase
{
    private const WHATWG_BASE_URI = 'https://raw.githubusercontent.com/web-platform-tests/wpt/master/url/resources/';
    private const CACHE_TTL = 86400 * 7; // 7 DAYS

    protected function loadTestData(string $url): array
    {
        $cache = new FilesystemAdapter('whatwg-test-suite', self::CACHE_TTL, __DIR__ . '/data');
        $cacheKey = $url;
        $uri = UriResolver::resolve(uri_for(self::WHATWG_BASE_URI), uri_for($url));
        $testData = $cache->getItem($cacheKey);

        if ($testData->isHit()) {
            $json = json_decode($testData->get(), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }

            throw new RuntimeException(sprintf(
                'The local copy of %s could not be converted into json : %s',
                (string) $uri,
                json_last_error_msg()
            ));
        }

        static $client;
        $client = $client ?? new Client(['base_uri' => self::WHATWG_BASE_URI]);
        $response = $client->get($url);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf(
                'Unable to download a fresh copy of the testsuite located at, %s',
                (string) $uri
            ));
        }

        $body = (string) $response->getBody();
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

        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'the downloaded copy of the testsuite located at %s is an invalid json file: %s',
                $url,
                json_last_error_msg()
            ));
        }

        $json = array_filter($json, '\is_array');
        $testData->set(json_encode($json));
        $cache->save($testData);

        return $json;
    }
}
