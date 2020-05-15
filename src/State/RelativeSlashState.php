<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBuilderInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#relative-slash-state
 */
class RelativeSlashState implements State
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
        assert($base !== null);

        if ($url->scheme->isSpecial() && ($codePoint === '/' || $codePoint === '\\')) {
            if ($codePoint === '\\') {
                // Validation error.
            }

            $parser->setState(new SpecialAuthorityIgnoreSlashesState());

            return self::RETURN_OK;
        }

        if ($codePoint === '/') {
            $parser->setState(new AuthorityState());

            return self::RETURN_OK;
        }

        $url->username = $base->username;
        $url->password = $base->password;
        $url->host = clone $base->host;
        $url->port = $base->port;
        $parser->setState(new PathState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
