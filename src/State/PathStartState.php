<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Path;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#path-start-state
 */
class PathStartState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if ($context->url->scheme->isSpecial()) {
            if ($codePoint === '\\') {
                // Validation error.
            }

            $context->state = new PathState();

            if ($codePoint !== '/' && $codePoint !== '\\') {
                $context->iter->prev();
            }

            return self::RETURN_OK;
        }

        if (!$context->isStateOverridden() && $codePoint === '?') {
            $context->url->query = '';
            $context->state = new QueryState();

            return self::RETURN_OK;
        }

        if (!$context->isStateOverridden() && $codePoint === '#') {
            $context->url->fragment = '';
            $context->state = new FragmentState();

            return self::RETURN_OK;
        }

        if ($codePoint !== CodePoint::EOF) {
            $context->state = new PathState();

            if ($codePoint !== '/') {
                $context->iter->prev();
            }

            return self::RETURN_OK;
        }

        if ($context->isStateOverridden() && $context->url->host->isNull()) {
            $context->url->path->push(new Path());
        }

        return self::RETURN_OK;
    }
}
