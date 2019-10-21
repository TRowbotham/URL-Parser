<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\State\HostnameState;
use Rowbot\URL\State\SchemeStartState;
use Rowbot\URL\State\State;
use Rowbot\URL\String\StringBuffer;
use Rowbot\URL\String\USVStringInterface;

use function strtolower;

class BasicURLParser implements URLParserInterface
{
    /**
     * @var string
     */
    private $encoding;

    /**
     * @var string|null
     */
    private $encodingOverride;

    /**
     * @var \Rowbot\URL\State\State
     */
    private $state;

    /**
     * @var \Rowbot\URL\State\State|null
     */
    private $stateOverride;

    public function __construct()
    {
        $this->encoding = 'utf-8';
        $this->encodingOverride = null;
        $this->state = new SchemeStartState();
        $this->stateOverride = null;
    }

    public function getOutputEncoding(): string
    {
        return $this->encoding;
    }

    public function isStateOverridden(): bool
    {
        return $this->stateOverride !== null;
    }

    public function isOverrideStateHostname(): bool
    {
        return $this->stateOverride instanceof HostnameState;
    }

    public function parse(
        USVStringInterface $input,
        URLRecord $base = null,
        string $encodingOverride = null,
        URLRecord $url = null,
        State $stateOverride = null
    ) {
        $count = 0;

        if ($url === null) {
            $url = new URLRecord();
            $input = $input->replaceRegex('/^[\x00-\x20]+|[\x00-\x20]+$/u', '', -1, $count);

            if ($count !== 0) {
                // Validation error.
            }
        }

        $input = $input->replaceRegex('/[\x09\x0A\x0D]+/u', '', -1, $count);

        if ($count !== 0) {
            // Validation error.
        }

        $this->stateOverride = $stateOverride;
        $this->state = $stateOverride ?? new SchemeStartState();
        $this->encodingOverride = $encodingOverride;
        $this->encoding = $encodingOverride ?? 'utf-8';
        $buffer = new StringBuffer();
        $iter = $input->getIterator();
        $length = $input->length();
        $iter->rewind();

        while (true) {
            $status = $this->state->handle(
                $this,
                $input,
                $iter,
                $buffer,
                $iter->current(),
                $url,
                $base
            );

            if ($status === State::RETURN_CONTINUE) {
                continue;
            }

            if ($status === State::RETURN_FAILURE) {
                return false;
            }

            if ($status === State::RETURN_BREAK || $iter->key() >= $length) {
                break;
            }

            $iter->next();
        }

        return $url;
    }

    public function setOutputEncoding(string $encoding): void
    {
        $this->encoding = strtolower($encoding);
    }

    public function setState(State $state): void
    {
        $this->state = $state;
    }
}
