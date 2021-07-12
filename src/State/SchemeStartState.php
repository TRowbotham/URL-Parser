<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
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
        if (strpbrk($codePoint, CodePoint::ASCII_ALPHA_MASK) === $codePoint) {
            $context->buffer->append(strtolower($codePoint));
            $context->state = new SchemeState();

            return self::RETURN_OK;
        }

        if (!$context->isStateOverridden()) {
            $context->state = new NoSchemeState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // Validation error.
        // Note: This indication of failure is used exclusively by the Location object's protocol
        // attribute.
        return self::RETURN_FAILURE;
    }
}
