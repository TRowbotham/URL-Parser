<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Path;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLParserInterface;
use Rowbot\URL\URLRecord;

use function strtolower;

/**
 * @see https://url.spec.whatwg.org/#scheme-state
 */
class SchemeState implements State
{
    public function handle(
        URLParserInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        string $codePoint,
        URLRecord $url,
        ?URLRecord $base
    ): int {
        if (
            CodePoint::isAsciiAlphaNumeric($codePoint)
            || $codePoint === '+'
            || $codePoint === '-'
            || $codePoint === '.'
        ) {
            $buffer->append(strtolower($codePoint));

            return self::RETURN_OK;
        }

        if ($codePoint === ':') {
            $stateIsOverridden = $parser->isStateOverridden();
            $bufferIsSpecialScheme = false;
            $candidateScheme = $buffer->toScheme();

            if ($stateIsOverridden) {
                $bufferIsSpecialScheme = $candidateScheme->isSpecial();
                $urlIsSpecial = $url->scheme->isSpecial();

                if ($urlIsSpecial && !$bufferIsSpecialScheme) {
                    return self::RETURN_BREAK;
                }

                if (!$urlIsSpecial && $bufferIsSpecialScheme) {
                    return self::RETURN_BREAK;
                }

                if (
                    $url->includesCredentials()
                    || ($url->port !== null && $candidateScheme->isFile())
                ) {
                    return self::RETURN_BREAK;
                }

                if (
                    $url->scheme->isFile()
                    && ($url->host->isEmpty() || $url->host->isNull())
                ) {
                    return self::RETURN_BREAK;
                }
            }

            $url->scheme = $candidateScheme;

            if ($stateIsOverridden) {
                if ($bufferIsSpecialScheme && $url->scheme->isDefaultPort($url->port)) {
                    $url->port = null;
                }

                return self::RETURN_BREAK;
            }

            $buffer->clear();
            $urlIsSpecial = $url->scheme->isSpecial();

            if ($url->scheme->isFile()) {
                if ($iter->peek(2) !== '//') {
                    // Validation error.
                }

                $parser->setState(new FileState());
            } elseif ($urlIsSpecial && $base !== null && $base->scheme->equals($url->scheme)) {
                // This means that base's cannot-be-a-base-URL flag is unset.
                $parser->setState(new SpecialRelativeOrAuthorityState());
            } elseif ($urlIsSpecial) {
                $parser->setState(new SpecialAuthoritySlashesState());
            } elseif ($iter->peek() === '/') {
                $parser->setState(new PathOrAuthorityState());
                $iter->next();
            } else {
                $url->cannotBeABaseUrl = true;
                $url->path->push(new Path());
                $parser->setState(new CannotBeABaseUrlPathState());
            }

            return self::RETURN_OK;
        }

        if (!$parser->isStateOverridden()) {
            $buffer->clear();
            $parser->setState(new NoSchemeState());

            // Reset the pointer to point at the first code point.
            $iter->rewind();

            return self::RETURN_CONTINUE;
        }

        // Validation error.
        // Note: This indication of failure is used exclusively by the Location object's protocol
        // attribute. Furthermore, the non-failure termination earlier in this state is an
        // intentional difference for defining that attribute.
        return self::RETURN_FAILURE;
    }
}
