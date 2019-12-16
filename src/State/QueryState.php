<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLParserInterface;
use Rowbot\URL\URLRecord;

use function mb_convert_encoding;
use function rawurlencode;
use function strlen;
use function substr;

/**
 * @see https://url.spec.whatwg.org/#query-state
 */
class QueryState implements State
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
            $parser->getOutputEncoding() !== 'utf-8'
            && (!$url->scheme->isSpecial() || $url->scheme->isWebsocket())
        ) {
            $parser->setOutputEncoding('utf-8');
        }

        if (!$parser->isStateOverridden() && $codePoint === '#') {
            $url->fragment = '';
            $parser->setState(new FragmentState());
        } elseif ($codePoint !== CodePoint::EOF) {
            if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
                // Validation error.
            }

            if (
                $codePoint === '%'
                && !$input->substr($iter->key() + 1)->startsWithTwoAsciiHexDigits()
            ) {
                // Validation error.
            }

            $encoding = $parser->getOutputEncoding();
            $bytes = mb_convert_encoding($codePoint, $encoding, 'utf-8');

            // This can happen when encoding code points using a non-UTF-8 encoding.
            if (substr($bytes, 0, 2) === '&#' && substr($bytes, -1) === ';') {
                $url->query .= '%26%23' . substr($bytes, 2, -1) . '%3B';
            } else {
                $length = strlen($bytes);

                for ($i = 0; $i < $length; ++$i) {
                    $byte = $bytes[$i];

                    if (
                        $bytes < "\x21"
                        || $bytes > "\x7E"
                        || $bytes === "\x22"
                        || $bytes === "\x23"
                        || $bytes === "\x3C"
                        || $bytes === "\x3E"
                        || ($bytes === "\x27" && $url->scheme->isSpecial())
                    ) {
                        $byte = rawurlencode($bytes[$i]);
                    }

                    $url->query .= $byte;
                }
            }
        }

        return self::RETURN_OK;
    }
}
