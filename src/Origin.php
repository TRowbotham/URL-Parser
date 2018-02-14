<?php
namespace Rowbot\URL;

/**
 * @see https://html.spec.whatwg.org/multipage/browsers.html#origin
 */
class Origin
{
    private $domain;
    private $host;
    private $isOpaque;
    private $port;
    private $scheme;

    private function __construct()
    {
        $this->isOpaque = true;
    }

    /**
     * Creates a tuple origin, which consists of a scheme, host, port, and
     * optionally a domain.
     *
     * @param string      $scheme
     * @param Host        $host
     * @param int|null    $port
     * @param string|null $domain
     *
     * @return self
     */
    public static function createTupleOrigin(
        $scheme,
        Host $host,
        $port,
        $domain = null
    ) {
        $origin = new self();
        $origin->domain = $domain;
        $origin->host = $host;
        $origin->isOpaque = false;
        $origin->port = $port;
        $origin->scheme = $scheme;

        return $origin;
    }

    /**
     * Creates an opaque origin. An opaque origin serializes to the string
     * 'null' and is only useful for testing equality.
     *
     * @return self
     */
    public static function createOpaqueOrigin()
    {
        return new self();
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#concept-origin-effective-domain
     *
     * @return object
     */
    public function getEffectiveDomain()
    {
        if ($this->isOpaque) {
            return 'null';
        }

        if ($this->domain) {
            return $this->domain;
        }


        return $this->host;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#concept-origin-opaque
     *
     * @return bool
     */
    public function isOpaque()
    {
        return $this->isOpaque;
    }

    /**
     * Checks if two origins are the same.
     *
     * @see https://html.spec.whatwg.org/multipage/browsers.html#same-origin
     *
     * @param Origin $other The origin being compared.
     *
     * @return bool
     */
    public function isSameOrigin(Origin $other)
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
     * @param Origin $other The origin being compared.
     *
     * @return bool
     */
    public function isSameOriginDomain(Origin $other)
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
            if ($this->scheme === $other->scheme
                && $this->domain !== null
                && $this->domain === $other->domain
            ) {
                return true;
            } elseif ($this->isSameOrigin($other)
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
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->isOpaque) {
            return 'null';
        }

        $result = $this->scheme;
        $result .= '://';
        $result .= (string) $this->host;

        if ($this->port !== null) {
            $result .= ':' . intval($this->port, 10);
        }

        return $result;
    }
}
