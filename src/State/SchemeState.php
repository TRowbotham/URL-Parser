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
        if (
            strpbrk($codePoint, CodePoint::ASCII_ALNUM_MASK) === $codePoint
            || $codePoint === '+'
            || $codePoint === '-'
            || $codePoint === '.'
        ) {
            $context->buffer->append(strtolower($codePoint));

            return self::RETURN_OK;
        }

        if ($codePoint === ':') {
            $stateIsOverridden = $context->isStateOverridden();
            $bufferIsSpecialScheme = false;
            $candidateScheme = $context->buffer->toScheme();

            if ($stateIsOverridden) {
                $bufferIsSpecialScheme = $candidateScheme->isSpecial();
                $urlIsSpecial = $context->url->scheme->isSpecial();

                if ($urlIsSpecial && !$bufferIsSpecialScheme) {
                    return self::RETURN_BREAK;
                }

                if (!$urlIsSpecial && $bufferIsSpecialScheme) {
                    return self::RETURN_BREAK;
                }

                if (
                    $context->url->includesCredentials()
                    || ($context->url->port !== null && $candidateScheme->isFile())
                ) {
                    return self::RETURN_BREAK;
                }

                if ($context->url->scheme->isFile() && $context->url->host->isEmpty()) {
                    return self::RETURN_BREAK;
                }
            }

            $context->url->scheme = $candidateScheme;

            if ($stateIsOverridden) {
                if ($bufferIsSpecialScheme && $context->url->scheme->isDefaultPort($context->url->port)) {
                    $context->url->port = null;
                }

                return self::RETURN_BREAK;
            }

            $context->buffer->clear();
            $urlIsSpecial = $context->url->scheme->isSpecial();

            if ($context->url->scheme->isFile()) {
                if ($context->iter->peek(2) !== '//') {
                    // Validation error.
                }

                $context->state = new FileState();
            } elseif (
                $urlIsSpecial
                && $context->base !== null
                && $context->base->scheme->equals($context->url->scheme)
            ) {
                // This means that base's cannot-be-a-base-URL flag is unset.
                $context->state = new SpecialRelativeOrAuthorityState();
            } elseif ($urlIsSpecial) {
                $context->state = new SpecialAuthoritySlashesState();
            } elseif ($context->iter->peek() === '/') {
                $context->state = new PathOrAuthorityState();
                $context->iter->next();
            } else {
                $context->url->cannotBeABaseUrl = true;
                $context->url->path->push(new Path());
                $context->state = new CannotBeABaseUrlPathState();
            }

            return self::RETURN_OK;
        }

        if (!$context->isStateOverridden()) {
            $context->buffer->clear();
            $context->state = new NoSchemeState();

            // Reset the pointer to point at the first code point.
            $context->iter->rewind();

            return self::RETURN_CONTINUE;
        }

        // Validation error.
        // Note: This indication of failure is used exclusively by the Location object's protocol
        // attribute. Furthermore, the non-failure termination earlier in this state is an
        // intentional difference for defining that attribute.
        return self::RETURN_FAILURE;
    }
}
