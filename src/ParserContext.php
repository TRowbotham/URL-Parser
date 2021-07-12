<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\State\HostnameState;
use Rowbot\URL\State\SchemeStartState;
use Rowbot\URL\State\State;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\Support\EncodingHelper;

final class ParserContext
{
    /**
     * @readonly
     *
     * @var \Rowbot\URL\URLRecord|null
     */
    public $base;

    /**
     * @readonly
     *
     * @var \Rowbot\URL\String\StringBufferInterface
     */
    public $buffer;

    /**
     * @readonly
     *
     * @var \Rowbot\URL\String\USVStringInterface
     */
    public $input;

    /**
     * @readonly
     *
     * @var \Rowbot\URL\String\StringIteratorInterface
     */
    public $iter;

    /**
     * @var \Rowbot\URL\State\State
     */
    public $state;

    /**
     * @readonly
     *
     * @var \Rowbot\URL\URLRecord
     */
    public $url;

    /**
     * @var string
     */
    private $encoding;

    /**
     * @var \Rowbot\URL\State\State|null
     */
    private $stateOverride;

    public function __construct(
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        URLRecord $url,
        ?URLRecord $base,
        ?State $stateOverride,
        ?string $encodingOverride
    ) {
        $this->input = $input;
        $this->iter = $iter;
        $this->buffer = $buffer;
        $this->url = $url;
        $this->base = $base;
        $this->encoding = EncodingHelper::getOutputEncoding($encodingOverride) ?? 'utf-8';
        $this->state = $stateOverride ?? new SchemeStartState();
        $this->stateOverride = $stateOverride;
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
        return $this->stateOverride instanceof HostnameState;
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
