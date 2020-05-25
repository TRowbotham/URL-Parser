<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#special-authority-slashes-state
 */
class SpecialAuthoritySlashesState implements State
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
        if ($codePoint === '/' && $iter->peek() === '/') {
            $parser->setState(new SpecialAuthorityIgnoreSlashesState());
            $iter->next();

            return self::RETURN_OK;
        }

        // Validation error.
        $parser->setState(new SpecialAuthorityIgnoreSlashesState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
