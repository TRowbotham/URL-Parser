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
        if ($context->base === null || ($context->base->cannotBeABaseUrl && $codePoint !== '#')) {
            // Validation error. Return failure.
            return self::RETURN_FAILURE;
        }

        if ($context->base->cannotBeABaseUrl && $codePoint === '#') {
            $context->url->scheme = clone $context->base->scheme;
            $context->url->path = clone $context->base->path;
            $context->url->query = $context->base->query;
            $context->url->fragment = '';
            $context->url->cannotBeABaseUrl = true;
            $context->state = new FragmentState();

            return self::RETURN_OK;
        }

        if (!$context->base->scheme->isFile()) {
            $context->state = new RelativeState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        $context->state = new FileState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
