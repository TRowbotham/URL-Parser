<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\ParserState;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\EncodeSet;
use Rowbot\URL\String\PercentEncoder;

/**
 * @see https://url.spec.whatwg.org/#authority-state
 */
class AuthorityState implements State
{
    private bool $atTokenSeen;

    private bool $passwordTokenSeen;

    public function __construct()
    {
        $this->atTokenSeen = false;
        $this->passwordTokenSeen = false;
    }

    public function handle(ParserContext $context, string $codePoint): int
    {
        do {
            // 1. If c is U+0040 (@), then:
            if ($codePoint === '@') {
                // 1.1. Validation error.
                $context->logger?->notice('host-missing', [
                    'input'  => (string) $context->input,
                    'column' => $context->iter->key() + 1,
                ]);

                // 1.2. If atSignSeen is true, then prepend "%40" to buffer.
                if ($this->atTokenSeen) {
                    $context->buffer->prepend('%40');
                }

                // 1.3. Set atSignSeen to true.
                $this->atTokenSeen = true;
                $username = '';
                $password = '';

                // 1.4. For each codePoint in buffer:
                foreach ($context->buffer as $bufferCodePoint) {
                    // 1.4.1. If codePoint is U+003A (:) and passwordTokenSeen is false, then set passwordTokenSeen to true
                    // and continue.
                    if ($bufferCodePoint === ':' && !$this->passwordTokenSeen) {
                        $this->passwordTokenSeen = true;

                        continue;
                    }

                    // 1.4.3. If passwordTokenSeen is true, then append encodedCodePoints to url’s password.
                    // 1.4.4. Otherwise, append encodedCodePoints to url’s username.
                    if ($this->passwordTokenSeen) {
                        $password .= $bufferCodePoint;
                    } else {
                        $username .= $bufferCodePoint;
                    }
                }

                // 1.4.2. Let encodedCodePoints be the result of running UTF-8 percent-encode codePoint using the
                // userinfo percent-encode set.
                $percentEncoder = new PercentEncoder();
                $context->url->username .= $percentEncoder->percentEncodeAfterEncoding(
                    'utf-8',
                    $username,
                    EncodeSet::USERINFO
                );
                $context->url->password .= $percentEncoder->percentEncodeAfterEncoding(
                    'utf-8',
                    $password,
                    EncodeSet::USERINFO
                );

                // 1.5. Set buffer to the empty string.
                $context->buffer->clear();

                return self::RETURN_OK;
            }

            // 2. Otherwise, if one of the following is true:
            //      - c is the EOF code point, U+002F (/), U+003F (?), or U+0023 (#)
            //      - url is special and c is U+005C (\)
            if (
                (
                    $codePoint === CodePoint::EOF
                    || $codePoint === '/'
                    || $codePoint === '?'
                    || $codePoint === '#'
                )
                || ($context->url->scheme->isSpecial() && $codePoint === '\\')
            ) {
                // 2.1. If atSignSeen is true and buffer is the empty string, validation error, return failure.
                if ($this->atTokenSeen && $context->buffer->isEmpty()) {
                    // Validation error.
                    $context->logger?->warning('invalid-credentials', [
                        'input'  => (string) $context->input,
                        'column' => $context->iter->key() + 1,
                    ]);

                    return self::RETURN_FAILURE;
                }

                // 2.2. Decrease pointer by the number of code points in buffer plus one, set buffer to the empty string,
                // and set state to host state.
                $context->iter->seek(-($context->buffer->length() + 1));
                $context->buffer->clear();
                $context->state = ParserState::HOST;

                return self::RETURN_OK;
            }

            // 3. Otherwise, append c to buffer.
            $context->buffer->append($codePoint);
            $context->iter->next();
            $codePoint = $context->iter->current();
        } while (true);
    }
}
