<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\HostParser;
use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#file-host-state
 */
class FileHostState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if (
            $codePoint === CodePoint::EOF
            || $codePoint === '/'
            || $codePoint === '\\'
            || $codePoint === '?'
            || $codePoint === '#'
        ) {
            $context->iter->prev();

            if (!$context->isStateOverridden() && $context->buffer->isWindowsDriveLetter()) {
                // Validation error
                $context->state = new PathState();

                return self::RETURN_OK;
            }

            // This is a (platform-independent) Windows drive letter quirk. $context->buffer is not reset
            // here and instead used in the path state.
            if ($context->buffer->isEmpty()) {
                $context->url->host = new StringHost();

                if ($context->isStateOverridden()) {
                    return self::RETURN_BREAK;
                }

                $context->state = new PathStartState();

                return self::RETURN_OK;
            }

            $host = HostParser::parse($context->buffer->toUtf8String(), !$context->url->scheme->isSpecial());

            if ($host === false) {
                return self::RETURN_FAILURE;
            }

            if ($host->isLocalHost()) {
                $host = new StringHost();
            }

            $context->url->host = $host;

            if ($context->isStateOverridden()) {
                return self::RETURN_BREAK;
            }

            $context->buffer->clear();
            $context->state = new PathStartState();

            return self::RETURN_OK;
        }

        $context->buffer->append($codePoint);

        return self::RETURN_OK;
    }
}
