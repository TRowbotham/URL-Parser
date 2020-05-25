<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#special-authority-ignore-slashes-state
 */
class SpecialAuthorityIgnoreSlashesState implements State
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
        if ($codePoint !== '/' && $codePoint !== '\\') {
            $parser->setState(new AuthorityState());
            $iter->prev();

            return self::RETURN_OK;
        }

        // Validation error.
        return self::RETURN_OK;
    }
}
