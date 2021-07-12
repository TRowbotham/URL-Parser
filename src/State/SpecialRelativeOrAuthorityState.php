<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

/**
 * @see https://url.spec.whatwg.org/#special-relative-or-authority-state
 */
class SpecialRelativeOrAuthorityState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if ($codePoint === '/' && $context->iter->peek() === '/') {
            $context->state = new SpecialAuthorityIgnoreSlashesState();
            $context->iter->next();

            return self::RETURN_OK;
        }

        // Validation error.
        $context->state = new RelativeState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
