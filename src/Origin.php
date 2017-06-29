<?php
namespace phpjs\urls;

/**
 * @see https://html.spec.whatwg.org/multipage/browsers.html#origin
 */
class Origin
{
    private $mDomain;
    private $mHost;
    private $mIsOpaque;
    private $mPort;
    private $mScheme;

    private function __construct()
    {
        $this->mIsOpaque = true;
    }

    /**
     * Creates a tuple origin, which consists of a scheme, host, port, and
     * optionally a domain.
     *
     * @param  string       $scheme
     * @param  Host         $host
     * @param  int|null     $port
     * @param  string|null  $domain
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
        $origin->mDomain = $domain;
        $origin->mHost = $host;
        $origin->mIsOpaque = false;
        $origin->mPort = $port;
        $origin->mScheme = $scheme;

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
        if ($this->mIsOpaque) {
            return $this;
        } elseif ($this->mDomain) {
            return $this->mDomain;
        } else {
            return $this->mHost;
        }
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#concept-origin-opaque
     *
     * @return bool
     */
    public function isOpaque()
    {
        return $this->mIsOpaque;
    }

    /**
     * Checks if two origins are the same.
     *
     * @see https://html.spec.whatwg.org/multipage/browsers.html#same-origin
     *
     * @param Origin $aOther The origin being compared.
     *
     * @return bool
     */
    public function isSameOrigin(Origin $aOther)
    {
        // If A and B are the same opaque origin, then return true.
        if ($this->mIsOpaque && $aOther->mIsOpaque && $this === $aOther) {
            return true;
        }

        // If A and B are both tuple origins and their schemes, hosts, and port
        // are identical, then return true.
        if (!$this->mIsOpaque && !$aOther->mIsOpaque) {
            return $this->mScheme === $aOther->mScheme &&
                $this->mHost->equals($aOther->mHost) &&
                $this->mPort === $aOther->mPort;
        }

        return false;
    }

    /**
     * Checks if the origin is both the same origin and the same domain.
     *
     * @see https://html.spec.whatwg.org/multipage/browsers.html#same-origin-domain
     *
     * @param Origin $aOther The origin being compared.
     *
     * @return bool
     */
    public function isSameOriginDomain(Origin $aOther)
    {
        // If A and B are the same opaque origin, then return true.
        if ($this->mIsOpaque && $aOther->mIsOpaque && $this === $aOther) {
            return true;
        }

        // If A and B are both tuple origins...
        if (!$this->mIsOpaque && !$aOther->mIsOpaque) {
            // If A and B's schemes are identical, and their domains are
            // identical and non-null, then return true. Otherwise, if A and B
            // are same origin and their domains are identical and null, then
            // return true.
            if ($this->mScheme === $aOther->mScheme &&
                $this->mDomain !== null &&
                $this->mDomain === $aOther->mDomain
            ) {
                return true;
            } elseif ($this->isSameOrigin($aOther) &&
                $this->mDomain === $aOther->mDomain &&
                $this->mDomain === null
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#ascii-serialisation-of-an-origin
     *
     * @return string
     */
    public function serializeAsASCII()
    {
        if ($this->mIsOpaque) {
            return 'null';
        }

        $result = $this->mScheme;
        $result .= '://';
        $result .= (string) $this->mHost;

        if ($this->mPort !== null) {
            $result .= ':' . intval($this->mPort, 10);
        }

        return $result;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/browsers.html#unicode-serialisation-of-an-origin
     *
     * @return string
     */
    public function serializeAsUnicode()
    {
        if ($this->mIsOpaque) {
            return 'null';
        }

        $unicodeHost = $this->mHost->isDomain()
            ? $this->mHost->domainToUnicode()
            : $this->mHost;
        $unicodeOrigin = new Origin(
            $this->mScheme,
            $unicodeHost,
            $this->mPort
        );

        return $unicodeOrigin->serializeAsASCII();
    }
}
