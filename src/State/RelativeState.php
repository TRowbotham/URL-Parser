<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

use function assert;

/**
 * @see https://url.spec.whatwg.org/#relative-state
 */
class RelativeState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        assert($context->base !== null && !$context->base->scheme->isFile());

        $context->url->scheme = clone $context->base->scheme;

        if ($codePoint === '/') {
            $context->state = new RelativeSlashState();

            return self::RETURN_OK;
        }

        if ($context->url->scheme->isSpecial() && $codePoint === '\\') {
            // Validation error
            $context->state = new RelativeSlashState();

            return self::RETURN_OK;
        }

        $context->url->username = $context->base->username;
        $context->url->password = $context->base->password;
        $context->url->host = clone $context->base->host;
        $context->url->port = $context->base->port;
        $context->url->path = clone $context->base->path;
        $context->url->query = $context->base->query;

        if ($codePoint === '?') {
            $context->url->query = '';
            $context->state = new QueryState();

            return self::RETURN_OK;
        }

        if ($codePoint === '#') {
            $context->url->fragment = '';
            $context->state = new FragmentState();

            return self::RETURN_OK;
        }

        if ($codePoint === CodePoint::EOF) {
            return self::RETURN_OK;
        }

        $context->url->query = null;
        $context->url->path->shorten($context->url->scheme);

        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
