<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\BasicURLParser;
use Rowbot\URL\Component\Host\HostParser;
use Rowbot\URL\Component\OpaqueOrigin;
use Rowbot\URL\Component\TupleOrigin;
use Rowbot\URL\Origin;
use Rowbot\URL\String\Utf8String;

class OriginTest extends TestCase
{
    public function originProvider(): array
    {
        $hostParser = new HostParser();
        $tuple = new TupleOrigin(
            'https',
            $hostParser->parse(new Utf8String('example.org'), false),
            null,
            null
        );
        $tupleDomain = new TupleOrigin(
            'https',
            $hostParser->parse(new Utf8String('example.org'), false),
            null,
            'example.org'
        );
        $opaque = new OpaqueOrigin();
        $urlParser = new BasicURLParser();

        return [
            [
                $tuple,
                new TupleOrigin(
                    'https',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    null,
                    null
                ),
                'same origin' => true,
                'same origin-domain' => true,
            ],
            [
                new TupleOrigin('https', $hostParser->parse(new Utf8String('example.org'), false), 314, null),
                new TupleOrigin('https', $hostParser->parse(new Utf8String('example.org'), false), 420, null),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                new TupleOrigin(
                    'https',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    314,
                    'example.org'
                ),
                new TupleOrigin(
                    'https',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    420,
                    'example.org'
                ),
                'same origin' => false,
                'same origin-domain' => true,
            ],
            [
                new TupleOrigin(
                    'https',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    null,
                    null
                ),
                new TupleOrigin(
                    'https',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    null,
                    'example.org'
                ),
                'same origin' => true,
                'same origin-domain' => false,
            ],
            [
                new TupleOrigin(
                    'https',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    null,
                    'example.org'
                ),
                new TupleOrigin(
                    'http',
                    $hostParser->parse(new Utf8String('example.org'), false),
                    null,
                    'example.org'
                ),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                new TupleOrigin('https', $hostParser->parse(new Utf8String('127.0.0.1'), false), null, null),
                new TupleOrigin('https', $hostParser->parse(new Utf8String('1.1.1.1'), false), null, null),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                new TupleOrigin('https', $hostParser->parse(new Utf8String('[::1]'), false), null, null),
                new TupleOrigin('https', $hostParser->parse(new Utf8String('[1::1]'), false), null, null),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                $urlParser->parse(new Utf8String('blob:https://foo.com'))->getOrigin(),
                $urlParser->parse(new Utf8String('https://foo.com'))->getOrigin(),
                'same origin' => true,
                'same origin-domain' => true,
            ],
            [
                $tuple,
                $tuple,
                'same origin' => true,
                'same origin-domain' => true,
            ],
            [
                $tuple,
                $tupleDomain,
                'same origin' => true,
                'same origin-domain' => false,
            ],
            [
                $opaque,
                new OpaqueOrigin(),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                $opaque,
                $opaque,
                'same origin' => true,
                'same origin-domain' => true,
            ],
            [
                $tuple,
                $opaque,
                'same origin' => false,
                'same origin-domain' => false,
            ],
        ];
    }

    /**
     * @dataProvider originProvider
     */
    public function testSameOriginConcept(
        Origin $originA,
        Origin $originB,
        bool $isSameOrigin,
        bool $isSameOriginDomain
    ): void {
        self::assertSame($isSameOrigin, $originA->isSameOrigin($originB));
        self::assertSame($isSameOriginDomain, $originA->isSameOriginDomain($originB));
    }

    public function testEffectiveDomainConcept(): void
    {
        $origin = new OpaqueOrigin();
        self::assertNull($origin->getEffectiveDomain());

        $parser = new BasicURLParser();
        $record = $parser->parse(new Utf8String('blob:https://foo.com'));
        $origin = $record->getOrigin();
        self::assertInstanceOf(TupleOrigin::class, $origin);
        self::assertNotNull($origin->getEffectiveDomain());
        self::assertSame('foo.com', $origin->getEffectiveDomain());

        $hostParser = new HostParser();
        $origin = new TupleOrigin(
            'https',
            $hostParser->parse(new Utf8String('example.org'), false),
            314,
            'example.org'
        );
        self::assertNotNull($origin->getEffectiveDomain());
        self::assertSame('example.org', $origin->getEffectiveDomain());
    }
}
