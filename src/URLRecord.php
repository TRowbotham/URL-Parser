<?php
namespace phpjs\urls;

class URLRecord
{
    /**
     * An ASCII string that identifies the type of URL.
     *
     * @var string
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
     * @var Host|string|null
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
     * @var string[]
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
        $this->scheme = '';
        $this->username = '';
        $this->password = '';
        $this->port = null;
        $this->path = [];
        $this->query = null;
        $this->fragment = null;
        $this->cannotBeABaseUrl = false;
    }

    /**
     * Used to set the URL's username anywhere outside of the URL parser.
     *
     * @see https://url.spec.whatwg.org/#set-the-username
     *
     * @param string $username The URL's username.
     */
    public function setUsername($username)
    {
        $this->username = '';

        while (($codePoint = mb_substr($username, 0, 1)) !== '') {
            $this->username .= URLUtils::utf8PercentEncode(
                $codePoint,
                URLUtils::ENCODE_SET_USERINFO
            );
            $username = mb_substr($username, 1);
        }
    }

    /**
     * Used to set the URL's passwrod anywhere outside of the URL parser.
     *
     * @see https://url.spec.whatwg.org/#set-the-password
     *
     * @param string $password The URL's password.
     */
    public function setPassword($password)
    {
        $this->password = '';

        while (($codePoint = mb_substr($password, 0, 1)) !== '') {
            $this->password .= URLUtils::utf8PercentEncode(
                $codePoint,
                URLUtils::ENCODE_SET_USERINFO
            );
            $password = mb_substr($password, 1);
        }
    }

    /**
     * Returns whether or not the URL's scheme is a special scheme.
     *
     * @see https://url.spec.whatwg.org/#is-special
     *
     * @return bool
     */
    public function isSpecial()
    {
        return isset(URLUtils::$specialSchemes[$this->scheme]);
    }

    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @see https://url.spec.whatwg.org/#shorten-a-urls-path
     */
    public function shortenPath()
    {
        $size = count($this->path);

        if ($size == 0) {
            return;
        }

        if ($this->scheme === 'file' &&
            $size == 1 &&
            preg_match(
                URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER,
                $this->path[0]
            )
        ) {
            return;
        }

        array_pop($this->path);
    }

    /**
     * Computes a URL's origin.
     *
     * @see https://url.spec.whatwg.org/#origin
     *
     * @return Origin
     */
    public function getOrigin()
    {
        switch ($this->scheme) {
            case 'blob':
                $url = URLParser::parseBasicUrl($this->path[0]);

                if ($url === false) {
                    // Return a new opaque origin
                    return new Origin(
                        $this->scheme,
                        $this->host,
                        $this->port,
                        null,
                        true
                    );
                }

                return $url->getOrigin();

            case 'ftp':
            case 'gopher':
            case 'http':
            case 'https':
            case 'ws':
            case 'wss':
                // Return a tuple consiting of URL's scheme, host, port, and
                // null
                return new Origin(
                    $this->scheme,
                    $this->host,
                    $this->port,
                    null
                );

            case 'file':
                // Unfortunate as it is, this is left as an exercise to the
                // reader. When in doubt, return a new opaque origin.
                return new Origin(
                    $this->scheme,
                    $this->host,
                    $this->port,
                    null,
                    true
                );

            default:
                // Return a new opaque origin.
                return new Origin(
                    $this->scheme,
                    $this->host,
                    $this->port,
                    null,
                    true
                );
        }
    }

    /**
     * Determines whether two URLs are equal to eachother.
     *
     * @see https://url.spec.whatwg.org/#concept-url-equals
     *
     * @param URLRecord $otherUrl A URL to compare equality against.
     *
     * @param bool $excludeFragment Optional argument that determines
     *     whether a URL's fragment should be factored into equality.
     *
     * @return bool
     */
    public function isEqual(URLRecord $otherUrl, $excludeFragment = false)
    {
        return $this->serializeURL($excludeFragment) ===
            $otherUrl->serializeURL($excludeFragment);
    }

    /**
     * Serializes a URL object.
     *
     * @see https://url.spec.whatwg.org/#concept-url-serializer
     *
     * @param bool $excludeFragment Optional argument, that, when
     *     specified will exclude the URL's fragment from being serialized.
     *
     * @return string
     */
    public function serializeURL($excludeFragment = false)
    {
        $output = $this->scheme . ':';

        if ($this->host !== null) {
            $output .= '//';

            if ($this->username !== '' || $this->password !== '') {
                $output .= $this->username;

                if ($this->password !== '') {
                    $output .= ':' . $this->password;
                }

                $output .= '@';
            }

            $output .= HostFactory::serialize($this->host);

            if ($this->port !== null) {
                $output .= ':' . $this->port;
            }
        } elseif ($this->host === null && $this->scheme === 'file') {
            $output .= '//';
        }

        if ($this->cannotBeABaseUrl) {
            $output .= $this->path[0];
        } else {
            if (!empty($this->path)) {
                $output .= '/';
            }

            $output .= implode('/', $this->path);
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
