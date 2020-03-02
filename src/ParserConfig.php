<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\State\HostnameState;
use Rowbot\URL\State\SchemeStartState;
use Rowbot\URL\State\State;
use Rowbot\URL\Support\EncodingHelper;

class ParserConfig implements ParserConfigInterface
{
    /**
     * @var string
     */
    private $encoding;

    /**
     * @var \Rowbot\URL\State\State
     */
    private $state;

    /**
     * @var \Rowbot\URL\State\State|null
     */
    private $stateOverride;

    public function __construct(?State $stateOverride, ?string $encodingOverride)
    {
        $this->encoding = EncodingHelper::getOutputEncoding($encodingOverride) ?? 'utf-8';
        $this->state = $stateOverride ?? new SchemeStartState();
        $this->stateOverride = $stateOverride;
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

    public function setOutputEncoding(string $encoding): void
    {
        $this->encoding = EncodingHelper::getOutputEncoding($encoding) ?? 'utf-8';
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(State $state): void
    {
        $this->state = $state;
    }
}
