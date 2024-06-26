<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Psr\Log\LoggerInterface;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\Support\EncodingHelper;

final class ParserContext
{
    public readonly ?URLRecord $base;

    public readonly StringBufferInterface $buffer;

    public readonly USVStringInterface $input;

    public readonly StringIteratorInterface $iter;

    public ParserState $state;

    public readonly URLRecord $url;

    public ?LoggerInterface $logger;

    private string $encoding;

    private ?ParserState $stateOverride;

    public function __construct(
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        URLRecord $url,
        ?URLRecord $base,
        ?ParserState $stateOverride,
        ?string $encodingOverride,
        ?LoggerInterface $logger
    ) {
        $this->input = $input;
        $this->iter = $iter;
        $this->buffer = $buffer;
        $this->url = $url;
        $this->base = $base;
        $this->encoding = EncodingHelper::getOutputEncoding($encodingOverride) ?? 'utf-8';
        $this->state = $stateOverride ?? ParserState::SCHEME_START;
        $this->stateOverride = $stateOverride;
        $this->logger = $logger;
    }

    /**
     * Returns the output encoding of the URL string.
     */
    public function getOutputEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Returns whether the parser's starting state was overriden. This occurs when using one of the
     * URL object's setters.
     */
    public function isStateOverridden(): bool
    {
        return $this->stateOverride !== null;
    }

    /**
     * Returns whether the parser's starting state was the Hostname state.
     */
    public function isOverrideStateHostname(): bool
    {
        return $this->stateOverride === ParserState::HOSTNAME;
    }

    /**
     * Changes the encoding of the resulting URL string. This only affects the query string portion
     * and it is only for use in the HTML specification. This should never be changed from the
     * default UTF-8 encoding.
     */
    public function setOutputEncoding(string $encoding): void
    {
        $this->encoding = EncodingHelper::getOutputEncoding($encoding) ?? 'utf-8';
    }
}
