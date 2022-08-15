<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

/**
 * @see https://url.spec.whatwg.org/#special-authority-slashes-state
 */
class SpecialAuthoritySlashesState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If c is U+002F (/) and remaining starts with U+002F (/), then set state to special authority ignore
        // slashes state and increase pointer by 1.
        if ($codePoint === '/' && $context->iter->peek() === '/') {
            $context->state = new SpecialAuthorityIgnoreSlashesState();
            $context->iter->next();

            return self::RETURN_OK;
        }

        // 2. Otherwise, validation error, set state to special authority ignore slashes state and decrease pointer
        // by 1.
        $context->state = new SpecialAuthorityIgnoreSlashesState();
        $context->logger?->notice('missing-solidus-before-authority');
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
