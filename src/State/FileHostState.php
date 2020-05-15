<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\Exception\HostException;
use Rowbot\URL\Component\Host\HostParser;
use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBuilderInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#file-host-state
 */
class FileHostState implements State
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
                $url->host = new StringHost();

                if ($parser->isStateOverridden()) {
                    return self::RETURN_BREAK;
                }

                $parser->setState(new PathStartState());

                return self::RETURN_OK;
            }

            $hostParser = new HostParser();

            try {
                $host = $hostParser->parse($buffer->toUtf8String(), !$url->scheme->isSpecial());
            } catch (HostException $e) {
                // Return failure.
                return self::RETURN_FAILURE;
            }

            if ($host->isLocalHost()) {
                $host = new StringHost();
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
