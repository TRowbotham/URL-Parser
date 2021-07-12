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
        if ($codePoint === '/') {
            $context->state = new AuthorityState();

            return self::RETURN_OK;
        }

        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
