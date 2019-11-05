<?php

namespace Rowbot\URL\Tests\WhatWg;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use function is_array;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function GuzzleHttp\Psr7\uri_for;

abstract class WhatwgTestCase extends TestCase
{
    private const WHATWG_BASE_URI = 'https://raw.githubusercontent.com/web-platform-tests/wpt/master/url/resources/';
    private const CACHE_TTL = 86400*7; // 7 DAYS

    protected function loadTestData(string $url): array
    {
        $cache = new FilesystemAdapter('whatwg-test-suite', self::CACHE_TTL, __DIR__.'/data');
        $cacheKey = $url;
        $uri = UriResolver::resolve(uri_for(self::WHATWG_BASE_URI), uri_for($url));
        $testData = $cache->getItem($cacheKey);

        if ($testData->isHit()) {
            $json = json_decode($testData->get(), true);
            if (JSON_ERROR_NONE === json_last_error()) {
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
        if (400 <= $response->getStatusCode()) {
            throw new RuntimeException(sprintf(
                'Unable to download a fresh copy of the testsuite located at, %s',
                (string) $uri
            ));
        }

        $json = json_decode((string) $response->getBody(), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf(
                'the downloaded copy of the testsuite located at %s is an invalid json file: %s',
                $url,
                json_last_error_msg()
            ));
        }

        $json = array_filter($json, 'is_array');
        $testData->set(json_encode($json));
        $testData->expiresAfter(self::CACHE_TTL);
        $cache->save($testData);

        return $json;
    }
}
