<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\ParserState;
use Rowbot\URL\String\CodePoint;

use function strpbrk;
use function strtolower;

/**
 * @see https://url.spec.whatwg.org/#scheme-start-state
 */
class SchemeStartState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If c is an ASCII alpha, append c, lowercased, to buffer, and set state to scheme state.
        if (strpbrk($codePoint, CodePoint::ASCII_ALPHA_MASK) === $codePoint) {
            $context->buffer->append(strtolower($codePoint));
            $context->state = ParserState::SCHEME;

            return self::RETURN_OK;
        }

        // 2. Otherwise, if state override is not given, set state to no scheme state and decrease pointer by 1.
        if (!$context->isStateOverridden()) {
            $context->state = ParserState::NO_SCHEME;
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // 3. Otherwise, return failure.
        //
        // Note: This indication of failure is used exclusively by the Location object's protocol
        // attribute.
        return self::RETURN_FAILURE;
    }
}
