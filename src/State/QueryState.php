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
        // 1. If encoding is not UTF-8 and one of the following is true:
        //      - url is not special
        //      - url’s scheme is "ws" or "wss"
        // then set encoding to UTF-8.
        if (
            $context->getOutputEncoding() !== 'utf-8'
            && (!$context->url->scheme->isSpecial() || $context->url->scheme->isWebsocket())
        ) {
            $context->setOutputEncoding('utf-8');
        }

        // 2. If state override is not given and c is U+0023 (#), then set url’s fragment to the empty string and state
        // to fragment state.
        if (!$context->isStateOverridden() && $codePoint === '#') {
            $context->url->fragment = '';
            $context->state = new FragmentState();

        // 3. Otherwise, if c is not the EOF code point:
        } elseif ($codePoint !== CodePoint::EOF) {
            // 3.1. If c is not a URL code point and not U+0025 (%), validation error.
            if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
                // Validation error.
            }

            // 3.2. If c is U+0025 (%) and remaining does not start with two ASCII hex digits, validation error.
            if (
                $codePoint === '%'
                && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
            ) {
                // Validation error.
            }

            // 3.3. Let bytes be the result of encoding c using encoding.
            $encoding = $context->getOutputEncoding();
            $bytes = mb_convert_encoding($codePoint, $encoding, 'utf-8');

            // This can happen when encoding code points using a non-UTF-8 encoding.
            //
            // 3.4. If bytes starts with `&#` and ends with 0x3B (;), then:
            if (strncmp($bytes, '&#', 2) === 0  && substr_compare($bytes, ';', -1) === 0) {
                // 3.4.1. Replace `&#` at the start of bytes with `%26%23`.
                // 3.4.2. Replace 0x3B (;) at the end of bytes with `%3B`.
                // 3.4.3. Append bytes, isomorphic decoded, to url’s query.
                $context->url->query .= '%26%23' . substr($bytes, 2, -1) . '%3B';

            // 3.5. Otherwise, for each byte in bytes:
            } else {
                $length = strlen($bytes);

                for ($i = 0; $i < $length; ++$i) {
                    $byte = $bytes[$i];

                    // 3.5.1. If one of the following is true
                    //      - byte is less than 0x21 (!)
                    //      - byte is greater than 0x7E (~)
                    //      - byte is 0x22 ("), 0x23 (#), 0x3C (<), or 0x3E (>)
                    //      - byte is 0x27 (') and url is special
                    // then append byte, percent encoded, to url’s query.
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

                    // 3.5.2. Otherwise, append a code point whose value is byte to url’s query.
                    $context->url->query .= $byte;
                }
            }
        }

        return self::RETURN_OK;
    }
}
