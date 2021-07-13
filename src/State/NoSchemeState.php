<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

/**
 * @see https://url.spec.whatwg.org/#no-scheme-state
 */
class NoSchemeState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If base is null, or base’s cannot-be-a-base-URL is true and c is not U+0023 (#), validation error, return
        // failure.
        if ($context->base === null || ($context->base->cannotBeABaseUrl && $codePoint !== '#')) {
            // Validation error. Return failure.
            return self::RETURN_FAILURE;
        }

        // 2. Otherwise, if base’s cannot-be-a-base-URL is true and c is U+0023 (#), set url’s scheme to base’s scheme,
        // url’s path to a clone of base’s path, url’s query to base’s query, url’s fragment to the empty string, set
        // url’s cannot-be-a-base-URL to true, and set state to fragment state.
        if ($context->base->cannotBeABaseUrl && $codePoint === '#') {
            $context->url->scheme = clone $context->base->scheme;
            $context->url->path = clone $context->base->path;
            $context->url->query = $context->base->query;
            $context->url->fragment = '';
            $context->url->cannotBeABaseUrl = true;
            $context->state = new FragmentState();

            return self::RETURN_OK;
        }

        // 3. Otherwise, if base’s scheme is not "file", set state to relative state and decrease pointer by 1.
        if (!$context->base->scheme->isFile()) {
            $context->state = new RelativeState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // 4. Otherwise, set state to file state and decrease pointer by 1.
        $context->state = new FileState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
