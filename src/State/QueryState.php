<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

use function mb_convert_encoding;
use function rawurlencode;
use function strlen;
use function strncmp;
use function substr;
use function substr_compare;

/**
 * @see https://url.spec.whatwg.org/#query-state
 */
class QueryState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if (
            $context->getOutputEncoding() !== 'utf-8'
            && (!$context->url->scheme->isSpecial() || $context->url->scheme->isWebsocket())
        ) {
            $context->setOutputEncoding('utf-8');
        }

        if (!$context->isStateOverridden() && $codePoint === '#') {
            $context->url->fragment = '';
            $context->state = new FragmentState();
        } elseif ($codePoint !== CodePoint::EOF) {
            if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
                // Validation error.
            }

            if (
                $codePoint === '%'
                && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
            ) {
                // Validation error.
            }

            $encoding = $context->getOutputEncoding();
            $bytes = mb_convert_encoding($codePoint, $encoding, 'utf-8');

            // This can happen when encoding code points using a non-UTF-8 encoding.
            if (strncmp($bytes, '&#', 2) === 0  && substr_compare($bytes, ';', -1) === 0) {
                $context->url->query .= '%26%23' . substr($bytes, 2, -1) . '%3B';
            } else {
                $length = strlen($bytes);

                for ($i = 0; $i < $length; ++$i) {
                    $byte = $bytes[$i];

                    if (
                        $bytes < "\x21"
                        || $bytes > "\x7E"
                        || $bytes === "\x22"
                        || $bytes === "\x23"
                        || $bytes === "\x3C"
                        || $bytes === "\x3E"
                        || ($bytes === "\x27" && $context->url->scheme->isSpecial())
                    ) {
                        $byte = rawurlencode($bytes[$i]);
                    }

                    $context->url->query .= $byte;
                }
            }
        }

        return self::RETURN_OK;
    }
}
