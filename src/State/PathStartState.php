<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#path-start-state
 */
class PathStartState implements State
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
        if ($url->scheme->isSpecial()) {
            if ($codePoint === '\\') {
                // Validation error.
            }

            $parser->setState(new PathState());

            if ($codePoint !== '/' && $codePoint !== '\\') {
                $iter->prev();
            }

            return self::RETURN_OK;
        }

        if (!$parser->isStateOverridden() && $codePoint === '?') {
            $url->query = '';
            $parser->setState(new QueryState());

            return self::RETURN_OK;
        }

        if (!$parser->isStateOverridden() && $codePoint === '#') {
            $url->fragment = '';
            $parser->setState(new FragmentState());

            return self::RETURN_OK;
        }

        if ($codePoint !== CodePoint::EOF) {
            $parser->setState(new PathState());

            if ($codePoint !== '/') {
                $iter->prev();
            }
        }

        return self::RETURN_OK;
    }
}
