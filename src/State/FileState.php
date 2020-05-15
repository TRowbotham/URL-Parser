<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\NullHost;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\Component\Scheme;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBuilderInterface;
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
        StringBuilderInterface $buffer,
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
            $url->host = clone $base->host;
            $url->path = clone $base->path;
            $url->query = $base->query;

            if ($codePoint === '?') {
                $url->query = '';
                $parser->setState(new QueryState());

                return self::RETURN_OK;
            }

            if ($codePoint === '#') {
                $url->fragment = '';
                $parser->setState(new FragmentState());

                return self::RETURN_OK;
            }

            if ($codePoint === CodePoint::EOF) {
                return self::RETURN_OK;
            }

            $url->query = null;

            // This is a (platform-independent) Windows drive letter quirk.
            if (!$input->substr($iter->key())->startsWithWindowsDriveLetter()) {
                $url->path->shorten($url->scheme);
            } else {
                // Validation error.
                $url->host = new NullHost();
                $url->path = new PathList();
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
