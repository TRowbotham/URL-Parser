<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#cannot-be-a-base-url-path-state
 */
class CannotBeABaseUrlPathState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If c is U+003F (?), then set url’s query to the empty string and state to query state.
        if ($codePoint === '?') {
            $context->url->query = '';
            $context->state = new QueryState();

            return self::RETURN_OK;
        }

        // 2. Otherwise, if c is U+0023 (#), then set url’s fragment to the empty string and state to fragment state.
        if ($codePoint === '#') {
            $context->url->fragment = '';
            $context->state = new FragmentState();

            return self::RETURN_OK;
        }

        // 3. Otherwise:
        // 3.1. If c is not the EOF code point, not a URL code point, and not U+0025 (%), validation error.
        if (
            $codePoint !== CodePoint::EOF
            && !CodePoint::isUrlCodePoint($codePoint)
            && $codePoint !== '%'
        ) {
            // Validation error.
        }

        // 3.2. If c is U+0025 (%) and remaining does not start with two ASCII hex digits, validation error.
        if (
            $codePoint === '%'
            && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error.
        }

        // 3.3. If c is not the EOF code point, UTF-8 percent-encode c using the C0 control percent-encode set and
        // append the result to url’s path[0].
        if ($codePoint !== CodePoint::EOF) {
            $context->url->path->first()->append(CodePoint::utf8PercentEncode($codePoint));
        }

        return self::RETURN_OK;
    }
}
