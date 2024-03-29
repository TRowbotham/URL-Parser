<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Path;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

use function strpbrk;
use function strtolower;

/**
 * @see https://url.spec.whatwg.org/#scheme-state
 */
class SchemeState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        // 1. If c is an ASCII alphanumeric, U+002B (+), U+002D (-), or U+002E (.), append c, lowercased, to buffer.
        if (
            strpbrk($codePoint, CodePoint::ASCII_ALNUM_MASK) === $codePoint
            || $codePoint === '+'
            || $codePoint === '-'
            || $codePoint === '.'
        ) {
            $context->buffer->append(strtolower($codePoint));

            return self::RETURN_OK;
        }

        // 2. Otherwise, if c is U+003A (:), then:
        if ($codePoint === ':') {
            $stateIsOverridden = $context->isStateOverridden();
            $bufferIsSpecialScheme = false;
            $candidateScheme = $context->buffer->toScheme();

            // 2.1. If state override is given, then:
            if ($stateIsOverridden) {
                $bufferIsSpecialScheme = $candidateScheme->isSpecial();
                $urlIsSpecial = $context->url->scheme->isSpecial();

                // 2.1.1. If url’s scheme is a special scheme and buffer is not a special scheme, then return.
                // 2.1.2. If url’s scheme is not a special scheme and buffer is a special scheme, then return.
                if ($urlIsSpecial xor $bufferIsSpecialScheme) {
                    return self::RETURN_BREAK;
                }

                // 2.1.3. If url includes credentials or has a non-null port, and buffer is "file", then return.
                if (
                    $context->url->includesCredentials()
                    || ($context->url->port !== null && $candidateScheme->isFile())
                ) {
                    return self::RETURN_BREAK;
                }

                // 2.1.4. If url’s scheme is "file" and its host is an empty host, then return.
                if ($context->url->scheme->isFile() && $context->url->host->isEmpty()) {
                    return self::RETURN_BREAK;
                }
            }

            // 2.2. Set url’s scheme to buffer.
            $context->url->scheme = $candidateScheme;

            // 2.3. If state override is given, then:
            if ($stateIsOverridden) {
                // 2.3.1. If url’s port is url’s scheme’s default port, then set url’s port to null.
                if ($bufferIsSpecialScheme && $context->url->scheme->isDefaultPort($context->url->port)) {
                    $context->url->port = null;
                }

                // 2.3.2. Return.
                return self::RETURN_BREAK;
            }

            // 2.4. Set buffer to the empty string.
            $context->buffer->clear();
            $urlIsSpecial = $context->url->scheme->isSpecial();

            // 2.5. If url’s scheme is "file", then:
            if ($context->url->scheme->isFile()) {
                // 2.5.1. If remaining does not start with "//", validation error.
                if ($context->iter->peek(2) !== '//') {
                    // Validation error.
                }

                // 2.5.2. Set state to file state.
                $context->state = new FileState();

            // 2.6. Otherwise, if url is special, base is non-null, and base’s scheme is equal to url’s scheme, set
            // state to special relative or authority state.
            } elseif (
                $urlIsSpecial
                && $context->base !== null
                && $context->base->scheme->equals($context->url->scheme)
            ) {
                // This means that base's cannot-be-a-base-URL flag is unset.
                $context->state = new SpecialRelativeOrAuthorityState();

            // 2.7. Otherwise, if url is special, set state to special authority slashes state.
            } elseif ($urlIsSpecial) {
                $context->state = new SpecialAuthoritySlashesState();

            // 2.8. Otherwise, if remaining starts with an U+002F (/), set state to path or authority state and
            // increase pointer by 1.
            } elseif ($context->iter->peek() === '/') {
                $context->state = new PathOrAuthorityState();
                $context->iter->next();

            // 2.9. Otherwise, set url’s cannot-be-a-base-URL to true, append an empty string to url’s path, and set
            // state to cannot-be-a-base-URL path state.
            } else {
                $context->url->cannotBeABaseUrl = true;
                $context->url->path->push(new Path());
                $context->state = new CannotBeABaseUrlPathState();
            }

            return self::RETURN_OK;
        }

        // 3. Otherwise, if state override is not given, set buffer to the empty string, state to no scheme state, and
        // start over (from the first code point in input).
        if (!$context->isStateOverridden()) {
            $context->buffer->clear();
            $context->state = new NoSchemeState();

            // Reset the pointer to point at the first code point.
            $context->iter->rewind();

            return self::RETURN_CONTINUE;
        }

        // 4. Otherwise, validation error, return failure.
        //
        // Note: This indication of failure is used exclusively by the Location object's protocol
        // attribute. Furthermore, the non-failure termination earlier in this state is an
        // intentional difference for defining that attribute.
        return self::RETURN_FAILURE;
    }
}
