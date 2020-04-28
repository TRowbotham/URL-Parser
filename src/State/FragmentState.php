<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#fragment-state
 */
class FragmentState implements State
{
    public function handle(
        ParserConfigInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        string $codePoint,
        URLRecord $url,
        ?URLRecord $base
    ): int {
        if ($codePoint === CodePoint::EOF) {
            // Do nothing.
            return self::RETURN_OK;
        }

        if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
            // Validation error.
        }

        if (
            $codePoint === '%'
            && !$input->substr($iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error.
        }

        $url->fragment .= CodePoint::utf8PercentEncode(
            $codePoint,
            CodePoint::FRAGMENT_PERCENT_ENCODE_SET
        );

        return self::RETURN_OK;
    }
}
