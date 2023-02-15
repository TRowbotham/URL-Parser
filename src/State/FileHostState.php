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
        do {
            // 1. If c is the EOF code point, U+002F (/), U+005C (\), U+003F (?), or U+0023 (#), then decrease pointer by 1
            // and then:
            if (
                $codePoint === CodePoint::EOF
                || $codePoint === '/'
                || $codePoint === '\\'
                || $codePoint === '?'
                || $codePoint === '#'
            ) {
                $context->iter->prev();

                // 1.1. If state override is not given and buffer is a Windows drive letter, validation error, set state to
                // path state.
                if (!$context->isStateOverridden() && $context->buffer->isWindowsDriveLetter()) {
                    // Validation error
                    $context->logger?->notice('file-invalid-Windows-drive-letter-host', [
                        'input'        => (string) $context->input,
                        'column_range' => [$context->iter->key(), $context->iter->key() + $context->buffer->length()],
                    ]);
                    $context->state = new PathState();

                    return self::RETURN_OK;
                }

                // This is a (platform-independent) Windows drive letter quirk. $context->buffer is not reset
                // here and instead used in the path state.
                //
                // 1.2. Otherwise, if buffer is the empty string, then:
                if ($context->buffer->isEmpty()) {
                    // 1.2.1. Set url’s host to the empty string.
                    $context->url->host = new StringHost();

                    // 1.2.2. If state override is given, then return.
                    if ($context->isStateOverridden()) {
                        return self::RETURN_BREAK;
                    }

                    // 1.2.3. Set state to path start state.
                    $context->state = new PathStartState();

                    return self::RETURN_OK;
                }

                // 1.3. Otherwise, run these steps:
                // 1.3.1. Let host be the result of host parsing buffer with url is not special.
                $parser = new HostParser();
                $host = $parser->parse($context, $context->buffer->toUtf8String(), !$context->url->scheme->isSpecial());

                // 1.3.2. If host is failure, then return failure.
                if ($host === false) {
                    return self::RETURN_FAILURE;
                }

                // 1.3.3. If host is "localhost", then set host to the empty string.
                if ($host->isLocalHost()) {
                    $host = new StringHost();
                }

                // 1.3.4. Set url’s host to host.
                $context->url->host = $host;

                // 1.3.5. If state override is given, then return.
                if ($context->isStateOverridden()) {
                    return self::RETURN_BREAK;
                }

                // 1.3.6. Set buffer to the empty string and state to path start state.
                $context->buffer->clear();
                $context->state = new PathStartState();

                return self::RETURN_OK;
            }

            // 2. Otherwise, append c to buffer.
            $context->buffer->append($codePoint);
            $context->iter->next();
            $codePoint = $context->iter->current();
        } while (true);
    }
}
