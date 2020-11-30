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
 * @see https://url.spec.whatwg.org/#port-state
 */
class PortState implements State
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
        if (strpbrk($codePoint, CodePoint::ASCII_DIGIT_MASK) === $codePoint) {
            $buffer->append($codePoint);

            return self::RETURN_OK;
        }

        if (
            (
                $codePoint === CodePoint::EOF
                || $codePoint === '/'
                || $codePoint === '?'
                || $codePoint === '#'
            )
            || ($url->scheme->isSpecial() && $codePoint === '\\')
            || $parser->isStateOverridden()
        ) {
            if (!$buffer->isEmpty()) {
                $port = $buffer->toInt();

                if ($port > 2 ** 16 - 1) {
                    // Validation error. Return failure.
                    return self::RETURN_FAILURE;
                }

                if ($url->scheme->isSpecial() && $url->scheme->isDefaultPort($port)) {
                    $url->port = null;
                } else {
                    $url->port = $port;
                }

                $buffer->clear();
            }

            if ($parser->isStateOverridden()) {
                return self::RETURN_BREAK;
            }

            $parser->setState(new PathStartState());
            $iter->prev();

            return self::RETURN_OK;
        }

        // Validation error. Return failure.
        return self::RETURN_FAILURE;
    }
}
