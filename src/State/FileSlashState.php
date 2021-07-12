<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

/**
 * @see https://url.spec.whatwg.org/#file-slash-state
 */
class FileSlashState implements State
{
    public function handle(ParserContext $context, string $codePoint): int
    {
        if ($codePoint === '/' || $codePoint === '\\') {
            if ($codePoint === '\\') {
                // Validation error.
            }

            $context->state = new FileHostState();

            return self::RETURN_OK;
        }

        if ($context->base !== null && $context->base->scheme->isFile()) {
            $context->url->host = clone $context->base->host;
            $path = $context->base->path->first();

            if (
                !$context->input->substr($context->iter->key())->startsWithWindowsDriveLetter()
                && $path->isNormalizedWindowsDriveLetter()
            ) {
                // This is a (platform-independent) Windows drive letter quirk. Both url’s and
                // base’s host are null under these conditions and therefore not copied.
                $context->url->path->push($path);
            }
        }

        $context->state = new PathState();
        $context->iter->prev();

        return self::RETURN_OK;
    }
}
