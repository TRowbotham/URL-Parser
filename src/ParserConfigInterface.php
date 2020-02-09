<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\State\State;

interface ParserConfigInterface
{
    /**
     * Returns the output encoding of the URL string.
     */
    public function getOutputEncoding(): string;

    /**
     * Returns whether the parser's starting state was the Hostname state.
     */
    public function isOverrideStateHostname(): bool;

    /**
     * Returns whether the parser's starting state was overriden. This occurs when using one of the
     * URL object's setters.
     */
    public function isStateOverridden(): bool;

    /**
     * Changes the encoding of the resulting URL string. This only affects the query string portion
     * and it is only for use in the HTML specification. This should never be changed from the
     * default UTF-8 encoding.
     */
    public function setOutputEncoding(string $encoding): void;

    /**
     * Returns the current state of the parser.
     */
    public function getState(): State;

    /**
     * Sets the parsers state to the given state.
     */
    public function setState(State $state): void;
}
