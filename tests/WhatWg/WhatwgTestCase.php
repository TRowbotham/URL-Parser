<?php

namespace Rowbot\URL\Tests\WhatWg;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

use function array_filter;
use function json_decode;
use function json_encode;
use function preg_replace;

use const JSON_THROW_ON_ERROR;

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
            $response = $client->get($url);

            // Replace all unpaired surrogate escape sequences with a \uFFFD escape sequence to avoid
            // json_decode() having a stroke and emitting a JSON_ERROR_UTF16 error causing the decode
            // to fail
            $body = preg_replace(
                '/
                    # Match a low surrogate not proceeded by a high surrogate
                    (?<!(?<high>\\\u[Dd][89AaBb][[:xdigit:]][[:xdigit:]]))
                    (?<low>\\\u[Dd][C-Fc-f][[:xdigit:]][[:xdigit:]])

                    # Match a high surrogate not followed by a low surrogate
                    |(?&high)(?!(?&low))
                /x',
                '\uFFFD',
                (string) $response->getBody()
            );

            // Remove comments and check to make sure it is valid JSON.
            $json = array_filter(json_decode($body, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR), 'is_array');

            return json_encode($json, JSON_THROW_ON_ERROR);
        });

        return json_decode($data, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
    }
}
