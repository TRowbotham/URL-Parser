<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLParserInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#file-slash-state
 */
class FileSlashState implements State
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
        if ($codePoint === '/' || $codePoint === '\\') {
            if ($codePoint === '\\') {
                // Validation error.
            }

            $parser->setState(new FileHostState());

            return self::RETURN_OK;
        }

        if (
            $base !== null
            && $base->scheme->isFile()
            && !$input->substr($iter->key())->startsWithWindowsDriveLetter()
        ) {
            $path = $base->path->first();

            if ($path->isNormalizedWindowsDriveLetter()) {
                // This is a (platform-independent) Windows drive letter quirk. Both url’s and
                // base’s host are null under these conditions and therefore not copied.
                $url->path->push($path);
            } else {
                $url->host = clone $base->host;
            }
        }

        $parser->setState(new PathState());
        $iter->prev();

        return self::RETURN_OK;
    }
}