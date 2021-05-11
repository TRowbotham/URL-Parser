<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLRecord;

use function strpbrk;
use function strtolower;

/**
 * @see https://url.spec.whatwg.org/#scheme-start-state
 */
class SchemeStartState implements State
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
        if (strpbrk($codePoint, CodePoint::ASCII_ALPHA_MASK) === $codePoint) {
            $buffer->append(strtolower($codePoint));
            $parser->setState(new SchemeState());

            return self::RETURN_OK;
        }

        if (!$parser->isStateOverridden()) {
            $parser->setState(new NoSchemeState());
            $iter->prev();

            return self::RETURN_OK;
        }

        // Validation error.
        // Note: This indication of failure is used exclusively by the Location object's protocol
        // attribute.
        return self::RETURN_FAILURE;
    }
}
