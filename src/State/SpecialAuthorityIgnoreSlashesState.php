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
        // 1. If c is neither U+002F (/) nor U+005C (\), then set state to authority state and decrease pointer by 1.
        if ($codePoint !== '/' && $codePoint !== '\\') {
            $context->state = new AuthorityState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // 2. Otherwise, validation error.
        return self::RETURN_OK;
    }
}
