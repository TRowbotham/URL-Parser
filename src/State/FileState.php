<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Scheme;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#file-state
 */
class FileState implements State
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
        $url->scheme = new Scheme('file');

        if ($codePoint === '/' || $codePoint === '\\') {
            if ($codePoint === '\\') {
                // Validation error
            }

            $parser->setState(new FileSlashState());

            return self::RETURN_OK;
        }

        if ($base !== null && $base->scheme->isFile()) {
            if ($codePoint === CodePoint::EOF) {
                $url->host = clone $base->host;
                $url->path = clone $base->path;
                $url->query = $base->query;

                return self::RETURN_OK;
            }

            if ($codePoint === '?') {
                $url->host = clone $base->host;
                $url->path = clone $base->path;
                $url->query = '';
                $parser->setState(new QueryState());

                return self::RETURN_OK;
            }

            if ($codePoint === '#') {
                $url->host = clone $base->host;
                $url->path = clone $base->path;
                $url->query = $base->query;
                $url->fragment = '';
                $parser->setState(new FragmentState());

                return self::RETURN_OK;
            }

            // This is a (platform-independent) Windows drive letter quirk.
            if (!$input->substr($iter->key())->startsWithWindowsDriveLetter()) {
                $url->host = clone $base->host;
                $url->path = clone $base->path;
                $url->path->shorten($url->scheme);
            } else {
                // Validation error.
            }

            $parser->setState(new PathState());
            $iter->prev();

            return self::RETURN_OK;
        }

        $parser->setState(new PathState());
        $iter->prev();

        return self::RETURN_OK;
    }
}
