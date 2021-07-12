<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\Component\Scheme;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

/**
 * @see https://url.spec.whatwg.org/#file-state
 */
class FileState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        $context->url->scheme = new Scheme('file');
        $context->url->host = new StringHost();

        if ($codePoint === '/' || $codePoint === '\\') {
            if ($codePoint === '\\') {
                // Validation error
            }

            $context->state = new FileSlashState();

            return self::RETURN_OK;
        }

        if ($context->base !== null && $context->base->scheme->isFile()) {
            $context->url->host = clone $context->base->host;
            $context->url->path = clone $context->base->path;
            $context->url->query = $context->base->query;

            if ($codePoint === '?') {
                $context->url->query = '';
                $context->state = new QueryState();

                return self::RETURN_OK;
            }

            if ($codePoint === '#') {
                $context->url->fragment = '';
                $context->state = new FragmentState();

                return self::RETURN_OK;
            }

            if ($codePoint === CodePoint::EOF) {
                return self::RETURN_OK;
            }

            $context->url->query = null;

            // This is a (platform-independent) Windows drive letter quirk.
            if (!$context->input->substr($context->iter->key())->startsWithWindowsDriveLetter()) {
                $context->url->path->shorten($context->url->scheme);
            } else {
                // Validation error.
                $context->url->path = new PathList();
            }

            $context->state = new PathState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
