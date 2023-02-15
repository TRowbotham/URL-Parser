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
        // 1. If base is null, or base has an opaque path and c is not U+0023 (#), validation error, return failure.
        if ($context->base === null || ($context->base->path->isOpaque() && $codePoint !== '#')) {
            // Validation error. Return failure.
            $context->logger?->warning('missing-scheme-non-relative-URL', [
                'input'  => (string) $context->input,
                'column' => $context->iter->key() + 1,
            ]);

            return self::RETURN_FAILURE;
        }

        // 2. Otherwise, if base has an opaque path and c is U+0023 (#), set url’s scheme to base’s scheme, url’s path
        // to base’s path, url’s query to base’s query, url’s fragment to the empty string, and set state to fragment
        // state.
        if ($context->base->path->isOpaque() && $codePoint === '#') {
            $context->url->scheme = clone $context->base->scheme;
            $context->url->path = clone $context->base->path;
            $context->url->query = $context->base->query;
            $context->url->fragment = '';
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
