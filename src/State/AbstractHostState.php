<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\HostParser;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#host-state
 */
abstract class AbstractHostState implements State
{
    /**
     * @var bool
     */
    private $isBracketOpen;

    public function __construct()
    {
        $this->isBracketOpen = false;
    }

    public function handle(ParserContext $context, string $codePoint): int
    {
        if ($context->isStateOverridden() && $context->url->scheme->isFile()) {
            $context->iter->prev();
            $context->state = new FileHostState();

            return self::RETURN_OK;
        }

        if ($codePoint === ':' && !$this->isBracketOpen) {
            if ($context->buffer->isEmpty()) {
                // Validation error. Return failure.
                return self::RETURN_FAILURE;
            }

            if ($context->isOverrideStateHostname()) {
                return self::RETURN_BREAK;
            }

            $host = HostParser::parse($context->buffer->toUtf8String(), !$context->url->scheme->isSpecial());

            if ($host === false) {
                return self::RETURN_FAILURE;
            }

            $context->url->host = $host;
            $context->buffer->clear();
            $context->state = new PortState();

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
            $context->iter->prev();

            if ($context->url->scheme->isSpecial() && $context->buffer->isEmpty()) {
                // Validation error. Return failure.
                return self::RETURN_FAILURE;
            }

            if (
                $context->isStateOverridden()
                && $context->buffer->isEmpty()
                && ($context->url->includesCredentials() || $context->url->port !== null)
            ) {
                // Validation error.
                return self::RETURN_BREAK;
            }

            $host = HostParser::parse($context->buffer->toUtf8String(), !$context->url->scheme->isSpecial());

            if ($host === false) {
                return self::RETURN_FAILURE;
            }

            $context->url->host = $host;
            $context->buffer->clear();
            $context->state = new PathStartState();

            if ($context->isStateOverridden()) {
                return self::RETURN_BREAK;
            }

            return self::RETURN_OK;
        }

        if ($codePoint === '[') {
            $this->isBracketOpen = true;
        } elseif ($codePoint === ']') {
            $this->isBracketOpen = false;
        }

        $context->buffer->append($codePoint);

        return self::RETURN_OK;
    }
}
