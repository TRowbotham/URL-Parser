<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use Rowbot\URL\BasicURLParser;
use Rowbot\URL\Component\Host\HostParser;
use Rowbot\URL\Origin;
use Rowbot\URL\String\Utf8String;

class OriginTest extends TestCase
{
    public function originProvider(): array
    {
        $tuple = Origin::createTupleOrigin(
            'https',
            HostParser::parse(new Utf8String('example.org'), false),
            null,
            null
        );
        $tupleDomain = Origin::createTupleOrigin(
            'https',
            HostParser::parse(new Utf8String('example.org'), false),
            null,
            'example.org'
        );
        $opaque = Origin::createOpaqueOrigin();
        $urlParser = new BasicURLParser();

        return [
            [
                $tuple,
                Origin::createTupleOrigin(
                    'https',
                    HostParser::parse(new Utf8String('example.org'), false),
                    null,
                    null
                ),
                'same origin' => true,
                'same origin-domain' => true,
            ],
            [
                Origin::createTupleOrigin('https', HostParser::parse(new Utf8String('example.org'), false), 314, null),
                Origin::createTupleOrigin('https', HostParser::parse(new Utf8String('example.org'), false), 420, null),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                Origin::createTupleOrigin(
                    'https',
                    HostParser::parse(new Utf8String('example.org'), false),
                    314,
                    'example.org'
                ),
                Origin::createTupleOrigin(
                    'https',
                    HostParser::parse(new Utf8String('example.org'), false),
                    420,
                    'example.org'
                ),
                'same origin' => false,
                'same origin-domain' => true,
            ],
            [
                Origin::createTupleOrigin(
                    'https',
                    HostParser::parse(new Utf8String('example.org'), false),
                    null,
                    null
                ),
                Origin::createTupleOrigin(
                    'https',
                    HostParser::parse(new Utf8String('example.org'), false),
                    null,
                    'example.org'
                ),
                'same origin' => true,
                'same origin-domain' => false,
            ],
            [
                Origin::createTupleOrigin(
                    'https',
                    HostParser::parse(new Utf8String('example.org'), false),
                    null,
                    'example.org'
                ),
                Origin::createTupleOrigin(
                    'http',
                    HostParser::parse(new Utf8String('example.org'), false),
                    null,
                    'example.org'
                ),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                Origin::createTupleOrigin('https', HostParser::parse(new Utf8String('127.0.0.1'), false), null, null),
                Origin::createTupleOrigin('https', HostParser::parse(new Utf8String('1.1.1.1'), false), null, null),
                'same origin' => false,
                'same origin-domain' => false,
            ],
            [
                Origin::createTupleOrigin('https', HostParser::parse(new Utf8String('[::1]'), false), null, null),
                Origin::createTupleOrigin('https', HostParser::parse(new Utf8String('[1::1]'), false), null, null),
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
                Origin::createOpaqueOrigin(),
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
        $this->assertSame($isSameOrigin, $originA->isSameOrigin($originB));
        $this->assertSame($isSameOriginDomain, $originA->isSameOriginDomain($originB));
    }

    public function testEffectiveDomainConcept(): void
    {
        $origin = Origin::createOpaqueOrigin();
        $this->assertTrue($origin->isOpaque());
        $this->assertNull($origin->getEffectiveDomain());

        $parser = new BasicURLParser();
        $record = $parser->parse(new Utf8String('blob:https://foo.com'));
        $origin = $record->getOrigin();
        $this->assertFalse($origin->isOpaque());
        $this->assertNotNull($origin->getEffectiveDomain());
        $this->assertSame('foo.com', $origin->getEffectiveDomain());

        $origin = Origin::createTupleOrigin(
            'https',
            HostParser::parse(new Utf8String('example.org'), false),
            314,
            'example.org'
        );
        $this->assertFalse($origin->isOpaque());
        $this->assertNotNull($origin->getEffectiveDomain());
        $this->assertSame('example.org', $origin->getEffectiveDomain());
    }
}
