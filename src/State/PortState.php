<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

use function strpbrk;

/**
 * @see https://url.spec.whatwg.org/#port-state
 */
class PortState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if (strpbrk($codePoint, CodePoint::ASCII_DIGIT_MASK) === $codePoint) {
            $context->buffer->append($codePoint);

            return self::RETURN_OK;
        }

        if (
            (
                $codePoint === CodePoint::EOF
                || $codePoint === '/'
                || $codePoint === '?'
                || $codePoint === '#'
            )
            || ($context->url->scheme->isSpecial() && $codePoint === '\\')
            || $context->isStateOverridden()
        ) {
            if (!$context->buffer->isEmpty()) {
                $port = $context->buffer->toInt();

                if ($port > 2 ** 16 - 1) {
                    // Validation error. Return failure.
                    return self::RETURN_FAILURE;
                }

                if ($context->url->scheme->isSpecial() && $context->url->scheme->isDefaultPort($port)) {
                    $context->url->port = null;
                } else {
                    $context->url->port = $port;
                }

                $context->buffer->clear();
            }

            if ($context->isStateOverridden()) {
                return self::RETURN_BREAK;
            }

            $context->state = new PathStartState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // Validation error. Return failure.
        return self::RETURN_FAILURE;
    }
}
