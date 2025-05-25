<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\ParserState;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\EncodeSet;
use Rowbot\URL\String\PercentEncoder;

/**
 * @see https://url.spec.whatwg.org/#cannot-be-a-base-url-path-state
 */
class OpaquePathState implements State
{
    public function handle(ParserContext $context, string $codePoint): StatusCode
    {
        $percentEncoder = null;

        do {
            // 1. If c is U+003F (?), then set url’s query to the empty string and state to query state.
            if ($codePoint === '?') {
                $context->url->query = '';
                $context->state = ParserState::QUERY;

                break;
            }

            // 2. Otherwise, if c is U+0023 (#), then set url’s fragment to the empty string and state to fragment state.
            if ($codePoint === '#') {
                $context->url->fragment = '';
                $context->state = ParserState::FRAGMENT;

                break;
            }

            // 3. Otherwise, if c is U+0020 SPACE:
            if ($codePoint === "\x20") {
                // 3.1. If remaining starts with U+003F (?) or U+003F (#), then append "%20" to url’s path.
                // 3.2. Otherwise, append U+0020 SPACE to url’s path.
                $remaining = $context->input->substr($context->iter->key() + 1);
                $appendChar = "\x20";

                if ($remaining->startsWith('?') || $remaining->startsWith('#')) {
                    $appendChar = '%20';
                }

                $context->url->path->first()->append($appendChar);

            // 4. Otherwise, if c is not the EOF code point:
            } elseif ($codePoint !== CodePoint::EOF) {
                // 4.1. If c is not a URL code point and not U+0025 (%), invalid-URL-unit validation error.
                if ($codePoint !== '%' && !CodePoint::isUrlCodePoint($codePoint)) {
                    // Validation error.
                    $context->logger?->notice('invalid-URL-unit', [
                        'input'  => (string) $context->input,
                        'column' => $context->iter->key() + 1,
                    ]);

                // 4.2. If c is U+0025 (%) and remaining does not start with two ASCII hex digits, invalid-URL-unit validation error.
                } elseif (
                    $codePoint === '%'
                    && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
                ) {
                    // Validation error.
                    $context->logger?->notice('invalid-URL-unit', [
                        'input'  => (string) $context->input,
                        'column' => $context->iter->key() + 1,
                    ]);
                }

                // 4.3. UTF-8 percent-encode c using the C0 control percent-encode set and append the result to url’s path.
                $percentEncoder ??= new PercentEncoder();
                $context->url->path->first()->append($percentEncoder->percentEncodeAfterEncoding(
                    'utf-8',
                    $codePoint,
                    EncodeSet::C0_CONTROL
                ));
            }

            $context->iter->next();
            $codePoint = $context->iter->current();
        } while ($codePoint !== CodePoint::EOF);

        return StatusCode::OK;
    }
}
