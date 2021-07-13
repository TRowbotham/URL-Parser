<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#fragment-state
 */
class FragmentState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If c is not the EOF code point, then:
        if ($codePoint === CodePoint::EOF) {
            // Do nothing.
            return self::RETURN_OK;
        }

        // 1.1. If c is not a URL code point and not U+0025 (%), validation error.
        if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
            // Validation error.
        }

        // 1.2. If c is U+0025 (%) and remaining does not start with two ASCII hex digits, validation error.
        if (
            $codePoint === '%'
            && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error.
        }

        // 1.3. UTF-8 percent-encode c using the fragment percent-encode set and append the result to url’s fragment.
        $context->url->fragment .= CodePoint::utf8PercentEncode(
            $codePoint,
            CodePoint::FRAGMENT_PERCENT_ENCODE_SET
        );

        return self::RETURN_OK;
    }
}
