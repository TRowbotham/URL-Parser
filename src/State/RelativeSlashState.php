<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

use function assert;

/**
 * @see https://url.spec.whatwg.org/#relative-slash-state
 */
class RelativeSlashState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        assert($context->base !== null);

        if ($context->url->scheme->isSpecial() && ($codePoint === '/' || $codePoint === '\\')) {
            if ($codePoint === '\\') {
                // Validation error.
            }

            $context->state = new SpecialAuthorityIgnoreSlashesState();

            return self::RETURN_OK;
        }

        if ($codePoint === '/') {
            $context->state = new AuthorityState();

            return self::RETURN_OK;
        }

        $context->url->username = $context->base->username;
        $context->url->password = $context->base->password;
        $context->url->host = clone $context->base->host;
        $context->url->port = $context->base->port;
        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
