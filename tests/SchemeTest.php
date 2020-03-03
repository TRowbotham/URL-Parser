<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rowbot\URL\Component\Scheme;

class SchemeTest extends TestCase
{
    public function specialSchemeNonNullDefaultPortProvider(): iterable
    {
        $reflection = new ReflectionClass(Scheme::class);
        $schemes = $reflection->getConstant('SPECIAL_SCHEMES');

        foreach ($schemes as $scheme => $port) {
            if ($port === null) {
                continue;
            }

            yield [$scheme, $port];
        }
    }

    /**
     * @dataProvider specialSchemeNonNullDefaultPortProvider
     */
    public function testIsDefaultPortReturnsTrueForNonNullPortSpecialSchemes(string $scheme, int $port): void
    {
        $scheme = new Scheme($scheme);
        $this->assertTrue($scheme->isDefaultPort($port));
    }

    public function schemeDefaultPortProvider(): array
    {
        return [
            ['sftp', 22],
            ['ssh', 22],
            ['smtp', 25],
            ['file', null], // special scheme, but has no default port
        ];
    }

    /**
     * @dataProvider schemeDefaultPortProvider
     */
    public function testIsDefaultPortReturnsFalseForNonSpecialSchemesAndNullPorts(string $scheme, ?int $port): void
    {
        $scheme = new Scheme($scheme);
        $this->assertFalse($scheme->isDefaultPort($port));
    }
}
