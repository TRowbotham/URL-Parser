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
 * @see https://url.spec.whatwg.org/#cannot-be-a-base-url-path-state
 */
class CannotBeABaseUrlPathState implements State
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
        if ($codePoint === '?') {
            $url->query = '';
            $parser->setState(new QueryState());

            return self::RETURN_OK;
        }

        if ($codePoint === '#') {
            $url->fragment = '';
            $parser->setState(new FragmentState());

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
            && !$input->substr($iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error.
        }

        if ($codePoint !== CodePoint::EOF) {
            $url->path->first()->append(CodePoint::utf8PercentEncode($codePoint));
        }

        return self::RETURN_OK;
    }
}
