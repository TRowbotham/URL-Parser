<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\HostParser;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

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

    public function handle(
        ParserConfigInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        string $codePoint,
        URLRecord $url,
        ?URLRecord $base
    ): int {
        if ($parser->isStateOverridden() && $url->scheme->isFile()) {
            $iter->prev();
            $parser->setState(new FileHostState());

            return self::RETURN_OK;
        }

        if ($codePoint === ':' && !$this->isBracketOpen) {
            if ($buffer->isEmpty()) {
                // Validation error. Return failure.
                return self::RETURN_FAILURE;
            }

            $host = HostParser::parse($buffer->toUtf8String(), !$url->scheme->isSpecial());

            if ($host === false) {
                return self::RETURN_FAILURE;
            }

            $url->host = $host;
            $buffer->clear();
            $parser->setState(new PortState());

            if ($parser->isOverrideStateHostname()) {
                return self::RETURN_BREAK;
            }

            return self::RETURN_OK;
        }

        if (
            (
                $codePoint === CodePoint::EOF
                || $codePoint === '/'
                || $codePoint === '?'
                || $codePoint === '#'
            )
            || ($url->scheme->isSpecial() && $codePoint === '\\')
        ) {
            $iter->prev();

            if ($url->scheme->isSpecial() && $buffer->isEmpty()) {
                // Validation error. Return failure.
                return self::RETURN_FAILURE;
            }

            if (
                $parser->isStateOverridden()
                && $buffer->isEmpty()
                && ($url->includesCredentials() || $url->port !== null)
            ) {
                // Validation error.
                return self::RETURN_BREAK;
            }

            $host = HostParser::parse($buffer->toUtf8String(), !$url->scheme->isSpecial());

            if ($host === false) {
                return self::RETURN_FAILURE;
            }

            $url->host = $host;
            $buffer->clear();
            $parser->setState(new PathStartState());

            if ($parser->isStateOverridden()) {
                return self::RETURN_BREAK;
            }

            return self::RETURN_OK;
        }

        if ($codePoint === '[') {
            $this->isBracketOpen = true;
        } elseif ($codePoint === ']') {
            $this->isBracketOpen = false;
        }

        $buffer->append($codePoint);

        return self::RETURN_OK;
    }
}
