<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

/**
 * @see https://url.spec.whatwg.org/#path-or-authority-state
 */
class PathOrAuthorityState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If c is U+002F (/), then set state to authority state.
        if ($codePoint === '/') {
            $context->state = new AuthorityState();

            return self::RETURN_OK;
        }

        // 2. Otherwise, set state to path state, and decrease pointer by 1.
        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
