<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLParserInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#relative-state
 */
class RelativeState implements State
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
        assert($base !== null);

        $url->scheme = clone $base->scheme;

        if ($codePoint === CodePoint::EOF) {
            $url->username = $base->username;
            $url->password = $base->password;
            $url->host = clone $base->host;
            $url->port = $base->port;
            $url->path = clone $base->path;
            $url->query = $base->query;

            return self::RETURN_OK;
        }

        if ($codePoint === '/') {
            $parser->setState(new RelativeSlashState());

            return self::RETURN_OK;
        }

        if ($codePoint === '?') {
            $url->username = $base->username;
            $url->password = $base->password;
            $url->host = clone $base->host;
            $url->port = $base->port;
            $url->path = clone $base->path;
            $url->query = '';
            $parser->setState(new QueryState());

            return self::RETURN_OK;
        }

        if ($codePoint === '#') {
            $url->username = $base->username;
            $url->password = $base->password;
            $url->host = clone $base->host;
            $url->port = $base->port;
            $url->path = clone $base->path;
            $url->query = $base->query;
            $url->fragment = '';
            $parser->setState(new FragmentState());

            return self::RETURN_OK;
        }

        if ($url->scheme->isSpecial() && $codePoint === '\\') {
            // Validation error
            $parser->setState(new RelativeSlashState());

            return self::RETURN_OK;
        }

        $url->username = $base->username;
        $url->password = $base->password;
        $url->host = clone $base->host;
        $url->port = $base->port;
        $url->path = clone $base->path;

        if (!$url->path->isEmpty()) {
            $url->path->pop();
        }

        $parser->setState(new PathState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
