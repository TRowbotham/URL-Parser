<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLRecord;

use function assert;

/**
 * @see https://url.spec.whatwg.org/#relative-state
 */
class RelativeState implements State
{
    public function handle(
        ParserConfigInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        string $codePoint,
        URLRecord $url,
        ?URLRecord $base
    ): int {
        assert($base !== null && !$base->scheme->isFile());

        $url->scheme = clone $base->scheme;

        if ($codePoint === '/') {
            $parser->setState(new RelativeSlashState());

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
        $url->query = $base->query;

        if ($codePoint === '?') {
            $url->query = '';
            $parser->setState(new QueryState());

            return self::RETURN_OK;
        }

        if ($codePoint === '#') {
            $url->fragment = '';
            $parser->setState(new FragmentState());

            return self::RETURN_OK;
        }

        if ($codePoint === CodePoint::EOF) {
            return self::RETURN_OK;
        }

        $url->query = null;
        $url->path->shorten($url->scheme);

        $parser->setState(new PathState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
