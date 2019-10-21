<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Host;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLParserInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#file-host-state
 */
class FileHostState implements State
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
        if (
            $codePoint === CodePoint::EOF
            || $codePoint === '/'
            || $codePoint === '\\'
            || $codePoint === '?'
            || $codePoint === '#'
        ) {
            $iter->prev();

            if (!$parser->isStateOverridden() && $buffer->isWindowsDriveLetter()) {
                // Validation error
                $parser->setState(new PathState());

                return self::RETURN_OK;
            }

            // This is a (platform-independent) Windows drive letter quirk. $buffer is not reset
            // here and instead used in the path state.
            if ($buffer->isEmpty()) {
                $url->host->setHost('');

                if ($parser->isStateOverridden()) {
                    return self::RETURN_BREAK;
                }

                $parser->setState(new PathStartState());

                return self::RETURN_OK;
            }

            $host = Host::parse((string) $buffer, !$url->scheme->isSpecial());

            if ($host === false) {
                // Return failure.
                return self::RETURN_FAILURE;
            }

            if ($host->equals('localhost')) {
                $host->setHost('');
            }

            $url->host = $host;

            if ($parser->isStateOverridden()) {
                return self::RETURN_BREAK;
            }

            $buffer->clear();
            $parser->setState(new PathStartState());

            return self::RETURN_OK;
        }

        $buffer->append($codePoint);

        return self::RETURN_OK;
    }
}
