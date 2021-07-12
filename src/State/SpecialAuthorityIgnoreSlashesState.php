<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

/**
 * @see https://url.spec.whatwg.org/#special-authority-ignore-slashes-state
 */
class SpecialAuthorityIgnoreSlashesState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if ($codePoint !== '/' && $codePoint !== '\\') {
            $context->state = new AuthorityState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // Validation error.
        return self::RETURN_OK;
    }
}
