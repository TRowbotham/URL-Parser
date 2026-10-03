<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host;

use ReflectionClass;
use ReflectionClassConstant;
use Rowbot\Idna\Idna;
use Rowbot\Idna\IdnaResult;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\Utf8String;

use function array_filter;
use function mb_check_encoding;
use function mb_strlen;
use function str_starts_with;
use function strtolower;

use const ARRAY_FILTER_USE_KEY;

class DomainParser
{
    /**
     * @see https://url.spec.whatwg.org/#forbidden-domain-code-point
     */
    private const FORBIDDEN_DOMAIN_CODEPOINTS = HostParser::FORBIDDEN_HOST_CODEPOINTS . '\x01-\x1F%\x7F';

    /**
     * @see https://url.spec.whatwg.org/#concept-domain-to-ascii
     */
    public function parse(ParserContext $context, string $domain, bool $beStrict): string|false
    {
        // 1. Let strictResult be the result of running domain parser ToASCII with domain and true.
        $strictResult = $this->toAscii($domain, true);

        // 2. If strictResult is a failure value, domain-to-ASCII validation error.
        // NOTE: This step does not return.
        if ($strictResult->hasErrors()) {
            $context->logger?->warning('domain-to-ASCII', [
                'input'        => $domain,
                'column_range' => [1, mb_strlen($domain, 'utf-8')],
                'idn_errors'   => $this->enumerateIdnaErrors($strictResult->getErrors()),
                'unicode_domain' => $this->toUnicode($domain),
            ]);
        }

        // 3. If beStrict is true:
        if ($beStrict) {
            // 3.1. If strictResult is a failure value, then return failure.
            // 3.2. Return strictResult.
            return $strictResult->hasErrors() ? false : $strictResult->getDomain();
        }

        // 4. Let result be null.
        $result = null;

        // 5. If domain is an ASCII string, then set result to domain, lowercased.
        if (mb_check_encoding($domain, 'ASCII')) {
            $result = strtolower($domain);

        // 6. Otherwise:
        } else {
            // 6.1. Set result to the result of running domain parser ToASCII with domain and false.
            $result = $this->toAscii($domain, false);

            // 6.2. If result is a failure value, then return failure.
            if ($result->hasErrors()) {
                return false;
            }

            $result = $result->getDomain();
        }

        // 7. If result is the empty string, then return failure.
        if ($result === '') {
            return false;
        }

        // 8. If result contains a forbidden domain code point, then return failure.
        if ((new Utf8String($result))->matches('/[' . self::FORBIDDEN_DOMAIN_CODEPOINTS . ']/u')) {
            return false;
        }

        // 9. Return result.
        return $result;
    }

    /**
     * @see https://url.spec.whatwg.org/#domain-parser-toascii
     */
    private function toAscii(string $domain, bool $beStrict): IdnaResult
    {
        return Idna::toAscii($domain, [
            'CheckHyphens'            => $beStrict,
            'CheckBidi'               => true,
            'CheckJoiners'            => true,
            'UseSTD3ASCIIRules'       => $beStrict,
            'Transitional_Processing' => false,
            'VerifyDnsLength'         => $beStrict,
            'IgnoreInvalidPunycode'   => false,
        ]);
    }

    /**
     * @see https://url.spec.whatwg.org/#concept-domain-to-unicode
     */
    private function toUnicode(string $domain): string
    {
        $result = Idna::toUnicode($domain, [
            'CheckHyphens'            => false,
            'CheckBidi'               => true,
            'CheckJoiners'            => true,
            'UseSTD3ASCIIRules'       => false,
            'Transitional_Processing' => false,
            'IgnoreInvalidPunycode'   => false,
        ]);

        if ($result->hasErrors()) {
            return $domain;
        }

        return $result->getDomain();
    }

    /**
     * @return list<string>
     */
    private function enumerateIdnaErrors(int $bitmask): array
    {
        $reflection = new ReflectionClass(Idna::class);
        $errorConstants = array_filter(
            $reflection->getConstants(ReflectionClassConstant::IS_PUBLIC),
            static fn (string $name): bool => str_starts_with($name, 'ERROR_'),
            ARRAY_FILTER_USE_KEY
        );
        $errors = [];

        foreach ($errorConstants as $name => $value) {
            // @phpstan-ignore binaryOp.invalid
            if (($value & $bitmask) !== 0) {
                $errors[] = $name;
            }
        }

        return $errors;
    }
}
