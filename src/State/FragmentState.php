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
        if ($codePoint === CodePoint::EOF) {
            // Do nothing.
            return self::RETURN_OK;
        }

        if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
            // Validation error.
        }

        if (
            $codePoint === '%'
            && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error.
        }

        $context->url->fragment .= CodePoint::utf8PercentEncode(
            $codePoint,
            CodePoint::FRAGMENT_PERCENT_ENCODE_SET
        );

        return self::RETURN_OK;
    }
}
