<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBuilderInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#special-relative-or-authority-state
 */
class SpecialRelativeOrAuthorityState implements State
{
    public function handle(
        ParserConfigInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBuilderInterface $buffer,
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
        $parser->setState(new RelativeState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
