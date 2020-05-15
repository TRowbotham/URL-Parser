<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\Component\Path;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBuilderInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
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
        StringBuilderInterface $buffer,
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

            if ($this->isDoubleDotPathSegment($buffer)) {
                $url->path->shorten($url->scheme);

                if ($codePoint !== '/' && !($urlIsSpecial && $codePoint === '\\')) {
                    $url->path->push(new Path());
                }
            } elseif (
                $this->isSingleDotPathSegment($buffer)
                && $codePoint !== '/'
                && !($urlIsSpecial && $codePoint === '\\')
            ) {
                $url->path->push(new Path());
            } elseif (!$this->isSingleDotPathSegment($buffer)) {
                if (
                    $url->scheme->isFile()
                    && $url->path->isEmpty()
                    && $buffer->isWindowsDriveLetter()
                ) {
                    if (!$url->host->isEmpty() && !$url->host->isNull()) {
                        // Validation error.
                        $url->host = new StringHost();
                    }

                    // This is a (platform-independent) Windows drive letter quirk.
                    $buffer->setCodePointAt(1, ':');
                }

                $url->path->push($buffer->toPath());
            }

            $buffer->clear();

            if (
                $url->scheme->isFile()
                && ($codePoint === CodePoint::EOF || $codePoint === '?' || $codePoint === '#')
            ) {
                $size = $url->path->count();

                while ($size-- > 1 && $url->path->first()->isEmpty()) {
                    // Validation error.
                    $url->path->shift();
                }
            }

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

    private function isDoubleDotPathSegment(StringBuilderInterface $buffer): bool
    {
        return isset(self::DOUBLE_DOT_SEGMENT[(string) $buffer]);
    }

    private function isSingleDotPathSegment(StringBuilderInterface $buffer): bool
    {
        return isset(self::SINGLE_DOT_SEGMENT[(string) $buffer]);
    }
}
