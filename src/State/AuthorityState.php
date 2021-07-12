<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#authority-state
 */
class AuthorityState implements State
{
    /**
     * @var bool
     */
    private $atTokenSeen;

    /**
     * @var bool
     */
    private $passwordTokenSeen;

    public function __construct()
    {
        $this->atTokenSeen = false;
        $this->passwordTokenSeen = false;
    }

    public function handle(ParserContext $context, string $codePoint): int
    {
        if ($codePoint === '@') {
            // Validation error.

            if ($this->atTokenSeen) {
                $context->buffer->prepend('%40');
            }

            $this->atTokenSeen = true;

            foreach ($context->buffer as $bufferCodePoint) {
                if ($bufferCodePoint === ':' && !$this->passwordTokenSeen) {
                    $this->passwordTokenSeen = true;

                    continue;
                }

                $userInfo = $this->passwordTokenSeen ? 'password' : 'username';
                $context->url->{$userInfo} .= CodePoint::utf8PercentEncode(
                    $bufferCodePoint,
                    CodePoint::USERINFO_PERCENT_ENCODE_SET
                );
            }

            $context->buffer->clear();

            return self::RETURN_OK;
        }

        if (
            (
                $codePoint === CodePoint::EOF
                || $codePoint === '/'
                || $codePoint === '?'
                || $codePoint === '#'
            )
            || ($context->url->scheme->isSpecial() && $codePoint === '\\')
        ) {
            if ($this->atTokenSeen && $context->buffer->isEmpty()) {
                // Validation error.
                return self::RETURN_FAILURE;
            }

            $context->iter->seek(-($context->buffer->length() + 1));
            $context->buffer->clear();
            $context->state = new HostState();

            return self::RETURN_OK;
        }

        $context->buffer->append($codePoint);

        return self::RETURN_OK;
    }
}
