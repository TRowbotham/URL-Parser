<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#no-scheme-state
 */
class NoSchemeState implements State
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
        if ($base === null || ($base->cannotBeABaseUrl && $codePoint !== '#')) {
            // Validation error. Return failure.
            return self::RETURN_FAILURE;
        }

        if ($base->cannotBeABaseUrl && $codePoint === '#') {
            $url->scheme = clone $base->scheme;
            $url->path = clone $base->path;
            $url->query = $base->query;
            $url->fragment = '';
            $url->cannotBeABaseUrl = true;
            $parser->setState(new FragmentState());

            return self::RETURN_OK;
        }

        if (!$base->scheme->isFile()) {
            $parser->setState(new RelativeState());
            $iter->prev();

            return self::RETURN_OK;
        }

        $parser->setState(new FileState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
