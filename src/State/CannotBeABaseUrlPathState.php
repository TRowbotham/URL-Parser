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
        if ($codePoint === '?') {
            $context->url->query = '';
            $context->state = new QueryState();

            return self::RETURN_OK;
        }

        if ($codePoint === '#') {
            $context->url->fragment = '';
            $context->state = new FragmentState();

            return self::RETURN_OK;
        }

        if (
            $codePoint !== CodePoint::EOF
            && !CodePoint::isUrlCodePoint($codePoint)
            && $codePoint !== '%'
        ) {
            // Validation error.
        }

        if (
            $codePoint === '%'
            && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error.
        }

        if ($codePoint !== CodePoint::EOF) {
            $context->url->path->first()->append(CodePoint::utf8PercentEncode($codePoint));
        }

        return self::RETURN_OK;
    }
}
