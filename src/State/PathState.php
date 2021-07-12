<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\Component\Path;
use Rowbot\URL\ParserContext;
use Rowbot\URL\String\CodePoint;

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

    public function handle(ParserContext $context, string $codePoint): int
    {
        if (
            $codePoint === CodePoint::EOF
            || $codePoint === '/'
            || ($context->url->scheme->isSpecial() && $codePoint === '\\')
            || (!$context->isStateOverridden() && ($codePoint === '?' || $codePoint === '#'))
        ) {
            $urlIsSpecial = $context->url->scheme->isSpecial();

            if ($urlIsSpecial && $codePoint === '\\') {
                // Validation error.
            }

            $stringBuffer = (string) $context->buffer;

            if (isset(self::DOUBLE_DOT_SEGMENT[$stringBuffer])) {
                $context->url->path->shorten($context->url->scheme);

                if ($codePoint !== '/' && !($urlIsSpecial && $codePoint === '\\')) {
                    $context->url->path->push(new Path());
                }
            } elseif (
                isset(self::SINGLE_DOT_SEGMENT[$stringBuffer])
                && $codePoint !== '/'
                && !($urlIsSpecial && $codePoint === '\\')
            ) {
                $context->url->path->push(new Path());
            } elseif (!isset(self::SINGLE_DOT_SEGMENT[$stringBuffer])) {
                if (
                    $context->url->scheme->isFile()
                    && $context->url->path->isEmpty()
                    && $context->buffer->isWindowsDriveLetter()
                ) {
                    // This is a (platform-independent) Windows drive letter quirk.
                    $context->buffer->setCodePointAt(1, ':');
                }

                $context->url->path->push($context->buffer->toPath());
            }

            $context->buffer->clear();

            if ($codePoint === '?') {
                $context->url->query = '';
                $context->state = new QueryState();
            } elseif ($codePoint === '#') {
                $context->url->fragment = '';
                $context->state = new FragmentState();
            }

            return self::RETURN_OK;
        }

        if (!CodePoint::isUrlCodePoint($codePoint) && $codePoint !== '%') {
            // Validation error
        }

        if (
            $codePoint === '%'
            && !$context->input->substr($context->iter->key() + 1)->startsWithTwoAsciiHexDigits()
        ) {
            // Validation error
        }

        $context->buffer->append(CodePoint::utf8PercentEncode(
            $codePoint,
            CodePoint::PATH_PERCENT_ENCODE_SET
        ));

        return self::RETURN_OK;
    }
}
