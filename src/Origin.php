<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\Component\Host\HostInterface;
use Rowbot\URL\Component\Host\NullHost;
use Stringable;

/**
 * @see https://html.spec.whatwg.org/multipage/browsers.html#origin
 */
class Origin implements Stringable
{
    private ?string $domain;

    private HostInterface $host;

    private bool $isOpaque;

    private ?int $port;

    private string $scheme;

    private function __construct(?string $domain, HostInterface $host, bool $isOpaque, ?int $port, string $scheme)
    {
        $this->domain = $domain;
        $this->host = $host;
        $this->isOpaque = $isOpaque;
        $this->port = $port;
        $this->scheme = $scheme;
    }

    /**
     * Creates a tuple origin, which consists of a scheme, host, port, and optionally a domain.
     */
    public static function createTupleOrigin(
        string $scheme,
        HostInterface $host,
        ?int $port,
        string $domain = null
    ): self {
        return new self($domain, $host, false, $port, $scheme);
    }

    /**
     * Creates an opaque origin. An opaque origin serializes to the string 'null' and is only useful
     * for testing equality.
     */
    public static function createOpaqueOrigin(): self
    {
        return new self(null, new NullHost(), true, null, '');
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#concept-origin-effective-domain
     */
    public function getEffectiveDomain(): ?string
    {
        if ($this->isOpaque) {
            return null;
        }

        if ($this->domain !== null) {
            return $this->domain;
        }

        return $this->host->getSerializer()->toFormattedString();
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#concept-origin-opaque
     */
    public function isOpaque(): bool
    {
        return $this->isOpaque;
    }

    /**
     * Checks if two origins are the same.
     *
     * @see https://html.spec.whatwg.org/multipage/browsers.html#same-origin
     *
     * @param self $other The origin being compared.
     */
    public function isSameOrigin(Origin $other): bool
    {
        // If A and B are the same opaque origin, then return true.
        if ($this->isOpaque && $other->isOpaque && $this === $other) {
            return true;
        }

        // If A and B are both tuple origins and their schemes, hosts, and port
        // are identical, then return true.
        if (!$this->isOpaque && !$other->isOpaque) {
            return $this->scheme === $other->scheme
                && $this->host->equals($other->host)
                && $this->port === $other->port;
        }

        return false;
    }

    /**
     * Checks if the origin is both the same origin and the same domain.
     *
     * @see https://html.spec.whatwg.org/multipage/browsers.html#same-origin-domain
     *
     * @param self $other The origin being compared.
     */
    public function isSameOriginDomain(Origin $other): bool
    {
        // If A and B are the same opaque origin, then return true.
        if ($this->isOpaque && $other->isOpaque && $this === $other) {
            return true;
        }

        // If A and B are both tuple origins...
        if (!$this->isOpaque && !$other->isOpaque) {
            // If A and B's schemes are identical, and their domains are
            // identical and non-null, then return true. Otherwise, if A and B
            // are same origin and their domains are identical and null, then
            // return true.
            if (
                $this->scheme === $other->scheme
                && $this->domain !== null
                && $this->domain === $other->domain
            ) {
                return true;
            } elseif (
                $this->isSameOrigin($other)
                && $this->domain === $other->domain
                && $this->domain === null
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/origin.html#ascii-serialisation-of-an-origin
     */
    public function __toString(): string
    {
        if ($this->isOpaque) {
            return 'null';
        }

        $result = $this->scheme;
        $result .= '://';
        $result .= $this->host->getSerializer()->toFormattedString();

        if ($this->port !== null) {
            $result .= ':' . $this->port;
        }

        return $result;
    }
}
