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
        // 1. Set url’s scheme to "file".
        $context->url->scheme = new Scheme('file');

        // 2. Set url’s host to the empty string.
        $context->url->host = new StringHost();

        // 3. If c is U+002F (/) or U+005C (\), then:
        if ($codePoint === '/' || $codePoint === '\\') {
            // 3.1. If c is U+005C (\), validation error.
            if ($codePoint === '\\') {
                // Validation error
                $context->logger?->notice('unexpected-reverse-solidus');
            }

            // 3.2. Set state to file slash state.
            $context->state = new FileSlashState();

            return self::RETURN_OK;
        }

        // 4. Otherwise, if base is non-null and base’s scheme is "file":
        if ($context->base !== null && $context->base->scheme->isFile()) {
            // 4.1. Set url’s host to base’s host, url’s path to a clone of base’s path, and url’s query to base’s
            // query.
            $context->url->host = clone $context->base->host;
            $context->url->path = clone $context->base->path;
            $context->url->query = $context->base->query;

            // 4.2. If c is U+003F (?), then set url’s query to the empty string and state to query state.
            if ($codePoint === '?') {
                $context->url->query = '';
                $context->state = new QueryState();

                return self::RETURN_OK;
            }

            // 4.3. Otherwise, if c is U+0023 (#), set url’s fragment to the empty string and state to fragment state.
            if ($codePoint === '#') {
                $context->url->fragment = '';
                $context->state = new FragmentState();

                return self::RETURN_OK;
            }

            // 4.4. Otherwise, if c is not the EOF code point:
            if ($codePoint === CodePoint::EOF) {
                return self::RETURN_OK;
            }

            // 4.4.1. Set url’s query to null.
            $context->url->query = null;

            // This is a (platform-independent) Windows drive letter quirk.
            //
            // 4.4.2. If the substring from pointer in input does not start with a Windows drive letter, then shorten
            // url’s path.
            if (!$context->input->substr($context->iter->key())->startsWithWindowsDriveLetter()) {
                $context->url->path->shorten($context->url->scheme);

            // 4.4.3. Otherwise:
            } else {
                // 4.4.3.1 Validation error.
                $context->logger?->notice('unexpected-windows-drive-letter');

                // 4.4.3.2. Set url’s path to an empty list.
                $context->url->path = new PathList();
            }

            // 4.4.4 Set state to path state and decrease pointer by 1.
            $context->state = new PathState();
            $context->iter->prev();

            return self::RETURN_OK;
        }

        // 5. Otherwise, set state to path state, and decrease pointer by 1.
        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
