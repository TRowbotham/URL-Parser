<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\Component\Host\NullHost;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\Component\Scheme;

class URLRecord
{
    /**
     * An ASCII string that identifies the type of URL.
     *
     * @var \Rowbot\URL\Component\Scheme
     */
    public $scheme;

    /**
     * An ASCII string identifying a username.
     *
     * @var string
     */
    public $username;

    /**
     * An ASCII string identifying a password.
     *
     * @var string
     */
    public $password;

    /**
     * @var \Rowbot\URL\Component\Host\HostInterface
     */
    public $host;

    /**
     * A 16-bit unsigned integer that identifies a networking port.
     *
     * @var int|null
     */
    public $port;

    /**
     * A list of zero or more ASCII strings holding data.
     *
     * @var \Rowbot\URL\Component\PathListInterface
     */
    public $path;

    /**
     * An ASCII string holding data.
     *
     * @var string|null
     */
    public $query;

    /**
     * An ASCII string holding data.
     *
     * @var string|null
     */
    public $fragment;

    /**
     * Identifies whether the URL can act as a base URL.
     *
     * @var bool
     */
    public $cannotBeABaseUrl;

    public function __construct()
    {
        $this->scheme = new Scheme();
        $this->username = '';
        $this->password = '';
        $this->host = new NullHost();
        $this->port = null;
        $this->path = new PathList();
        $this->query = null;
        $this->fragment = null;
        $this->cannotBeABaseUrl = false;
    }

    public function __clone()
    {
        $this->scheme = clone $this->scheme;
        $this->host = clone $this->host;
        $this->path = clone $this->path;
    }

    /**
     * Whether or not a URL can have a username, password, or port set.
     *
     * @see https://url.spec.whatwg.org/#cannot-have-a-username-password-port
     */
    public function cannotHaveUsernamePasswordPort(): bool
    {
        return $this->host->isNull()
            || $this->host->isEmpty()
            || $this->cannotBeABaseUrl
            || $this->scheme->isFile();
    }

    /**
     * Whether or not the URL has a username or password.
     *
     * @see https://url.spec.whatwg.org/#include-credentials
     */
    public function includesCredentials(): bool
    {
        return $this->username !== '' || $this->password !== '';
    }

    /**
     * Computes a URL's origin.
     *
     * @see https://url.spec.whatwg.org/#origin
     *
     * @return \Rowbot\URL\Origin
     */
    public function getOrigin(): Origin
    {
        if ($this->scheme->isBlob()) {
            $parser = new BasicURLParser();
            $url = $parser->parse($this->path->first()->toUtf8String());

            if ($url === false) {
                // Return a new opaque origin
                return Origin::createOpaqueOrigin();
            }

            return $url->getOrigin();
        }

        if ($this->scheme->isFile()) {
            // Unfortunate as it is, this is left as an exercise to the
            // reader. When in doubt, return a new opaque origin.
            return Origin::createOpaqueOrigin();
        }

        if ($this->scheme->isSpecial()) {
            // Return a tuple consiting of URL's scheme, host, port, and null
            return Origin::createTupleOrigin(
                (string) $this->scheme,
                $this->host,
                $this->port,
                null
            );
        }

        // Return a new opaque origin.
        return Origin::createOpaqueOrigin();
    }

    /**
     * Determines whether two URLs are equal to eachother.
     *
     * @see https://url.spec.whatwg.org/#concept-url-equals
     *
     * @param self $otherUrl        A URL to compare equality against.
     * @param bool $excludeFragment (optional) determines whether a URL's fragment should be factored into equality.
     */
    public function isEqual(URLRecord $otherUrl, bool $excludeFragment = false): bool
    {
        return $this->serializeURL($excludeFragment) === $otherUrl->serializeURL($excludeFragment);
    }

    /**
     * Serializes a URL object.
     *
     * @see https://url.spec.whatwg.org/#concept-url-serializer
     *
     * @param bool $excludeFragment (optional) When specified it will exclude the URL's fragment from being serialized.
     */
    public function serializeURL(bool $excludeFragment = false): string
    {
        $output = $this->scheme . ':';
        $isNullHost = $this->host->isNull();

        if (!$isNullHost) {
            $output .= '//';

            if ($this->username !== '' || $this->password !== '') {
                $output .= $this->username;

                if ($this->password !== '') {
                    $output .= ':' . $this->password;
                }

                $output .= '@';
            }

            $output .= $this->host->getSerializer()->toFormattedString();

            if ($this->port !== null) {
                $output .= ':' . $this->port;
            }
        }

        if ($this->cannotBeABaseUrl) {
            $output .= $this->path->first();
        } else {
            $pathCount = $this->path->count();

            if ($isNullHost && $pathCount > 1 && $this->path->first()->isEmpty()) {
                $output .= '/.';
            }

            // Needed since implode() doesn't add a starting "/" when there is only one path segment.
            if ($pathCount > 0) {
                $output .= '/';
            }

            $output .= $this->path;
        }

        if ($this->query !== null) {
            $output .= '?' . $this->query;
        }

        if (!$excludeFragment && $this->fragment !== null) {
            $output .= '#' . $this->fragment;
        }

        return $output;
    }
}
