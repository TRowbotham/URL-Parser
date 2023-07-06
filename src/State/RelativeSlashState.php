<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\ParserState;

use function assert;

/**
 * @see https://url.spec.whatwg.org/#relative-slash-state
 */
class RelativeSlashState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        assert($context->base !== null);

        // 1. If url is special and c is U+002F (/) or U+005C (\), then:
        if ($context->url->scheme->isSpecial() && ($codePoint === '/' || $codePoint === '\\')) {
            // 1.1. If c is U+005C (\), validation error.
            if ($codePoint === '\\') {
                // Validation error.
                $context->logger?->notice('invalid-reverse-solidus', [
                    'input'  => (string) $context->input,
                    'column' => $context->iter->key() + 1,
                ]);
            }

            // 1.2. Set state to special authority ignore slashes state.
            $context->state = ParserState::SPECIAL_AUTHORITY_IGNORE_SLASHES;

            return self::RETURN_OK;
        }

        // 2. Otherwise, if c is U+002F (/), then set state to authority state.
        if ($codePoint === '/') {
            $context->state = ParserState::AUTHORITY;

            return self::RETURN_OK;
        }

        // 3. Otherwise, set url’s username to base’s username, url’s password to base’s password, url’s host to base’s
        // host, url’s port to base’s port, state to path state, and then, decrease pointer by 1.
        $context->url->username = $context->base->username;
        $context->url->password = $context->base->password;
        $context->url->host = clone $context->base->host;
        $context->url->port = $context->base->port;
        $context->state = ParserState::PATH;
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
