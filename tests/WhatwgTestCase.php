<?php

namespace Rowbot\URL\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\UriResolver;
use PHPUnit_Framework_TestCase as TestCase;
use RuntimeException;
use Symfony\Component\Cache\Simple\FilesystemCache;
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
        $cache = new FilesystemCache('whatwg-test-suite', self::CACHE_TTL, __DIR__.'/data');
        $cacheKey = $url;
        $uri = UriResolver::resolve(uri_for(self::WHATWG_BASE_URI), uri_for($url));

        if ($cache->has($cacheKey)) {
            $json = json_decode($cache->get($cacheKey), true);
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
        $cache->set($cacheKey, json_encode($json), self::CACHE_TTL);

        return $json;
    }

    public function urlTestDataSuccessProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (!isset($inputs['failure'])) {
                yield [(object) $inputs];
            }
        }
    }

    public function urlTestDataFailureProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (isset($inputs['failure'])) {
                yield [(object) $inputs];
            }
        }
    }

    public function urlTestDataOriginProvider(): iterable
    {
        foreach ($this->loadTestData('urltestdata.json') as $inputs) {
            if (isset($inputs['origin'])) {
                yield [(object) $inputs];
            }
        }
    }

    public function urlSetterGetterDataProvider(): iterable
    {
        foreach ($this->loadTestData('setters_tests.json') as $key => $tests) {
            if ('comment' === $key) {
                continue;
            }

            foreach ($tests as $inputs) {
                unset($inputs['comment']);
                $inputs['setter'] = $key;

                yield [(object) $inputs];
            }
        }
    }

    public function toAsciiTestProvider(): iterable
    {
        foreach ($this->loadTestData('toascii.json') as $inputs) {
            if (isset($inputs['comment'])) {
                yield [(object) $inputs];
            }
        }
    }
}
