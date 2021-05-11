<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Path;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#path-state
 */
class PathState implements State
{
    /**
     * @see https://url.spec.whatwg.org/#double-dot-path-segment
     */
    private const DOUBLE_DOT_SEGMENT = [
        '..'     => '',
        '.%2e'   => '',
        '.%2E'   => '',
        '%2e.'   => '',
        '%2E.'   => '',
        '%2e%2e' => '',
        '%2E%2E' => '',
        '%2e%2E' => '',
        '%2E%2e' => '',
    ];

    /**
     * @see https://url.spec.whatwg.org/#single-dot-path-segment
     */
    private const SINGLE_DOT_SEGMENT = [
        '.'   => '',
        '%2e' => '',
        '%2E' => '',
    ];

    public function handle(
        ParserConfigInterface $parser,
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
            || ($url->scheme->isSpecial() && $codePoint === '\\')
            || (!$parser->isStateOverridden() && ($codePoint === '?' || $codePoint === '#'))
        ) {
            $urlIsSpecial = $url->scheme->isSpecial();

            if ($urlIsSpecial && $codePoint === '\\') {
                // Validation error.
            }

            $stringBuffer = (string) $buffer;

            if (isset(self::DOUBLE_DOT_SEGMENT[$stringBuffer])) {
                $url->path->shorten($url->scheme);

                if ($codePoint !== '/' && !($urlIsSpecial && $codePoint === '\\')) {
                    $url->path->push(new Path());
                }
            } elseif (
                isset(self::SINGLE_DOT_SEGMENT[$stringBuffer])
                && $codePoint !== '/'
                && !($urlIsSpecial && $codePoint === '\\')
            ) {
                $url->path->push(new Path());
            } elseif (!isset(self::SINGLE_DOT_SEGMENT[$stringBuffer])) {
                if (
                    $url->scheme->isFile()
                    && $url->path->isEmpty()
                    && $buffer->isWindowsDriveLetter()
                ) {
                    // This is a (platform-independent) Windows drive letter quirk.
                    $buffer->setCodePointAt(1, ':');
                }

                $url->path->push($buffer->toPath());
            }

            $buffer->clear();

            if ($codePoint === '?') {
                $url->query = '';
                $parser->setState(new QueryState());
            } elseif ($codePoint === '#') {
                $url->fragment = '';
                $parser->setState(new FragmentState());
            }

            return self::RETURN_OK;
        }

        if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
            // Validation error
        }

        if (
            $codePoint === '%'
            && !$input->substr($iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error
        }

        $buffer->append(CodePoint::utf8PercentEncode(
            $codePoint,
            CodePoint::PATH_PERCENT_ENCODE_SET
        ));

        return self::RETURN_OK;
    }
}
