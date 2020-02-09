<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#path-or-authority-state
 */
class PathOrAuthorityState implements State
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
        if ($codePoint === '/') {
            $parser->setState(new AuthorityState());

            return self::RETURN_OK;
        }

        $parser->setState(new PathState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
