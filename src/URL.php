<?php
namespace phpjs\urls;

use JsonSerializable;
use phpjs\urls\exception\TypeError;

/**
 * Represents a URL that can be manipulated.
 *
 * @see https://url.spec.whatwg.org/#api
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URL
 */
class URL implements JsonSerializable
{
    private $mSearchParams;
    private $mUrl;

    public function __construct($aUrl, $aBase = null)
    {
        $this->mSearchParams = null;
        $this->mUrl = null;
        $parsedBase = null;

        if ($aBase) {
            $parsedBase = URLParser::parseBasicUrl($aBase);

            if ($parsedBase === false) {
                throw new TypeError($aBase . ' is not a valid URL.');
                return;
            }
        }

        $parsedURL = URLParser::parseBasicUrl($aUrl, $parsedBase);

        if ($parsedURL === false) {
            throw new TypeError($aUrl . ' is not a valid URL.');
            return;
        }

        $this->mUrl = $parsedURL;
        $query = $this->mUrl->query ?: '';
        $this->mSearchParams = URLSearchParams::create($query, $parsedURL);
    }

    public function __destruct()
    {
        $this->mSearchParams = null;
        $this->mUrl = null;
    }

    public function __clone()
    {
        $this->mUrl = clone $this->mUrl;
        $this->mSearchParams = URLSearchParams::create(
            $this->mSearchParams,
            $this->mUrl
        );
    }

    public function __get($aName)
    {
        switch ($aName) {
            case 'hash':
                if ($this->mUrl->fragment === null ||
                    $this->mUrl->fragment === ''
                ) {
                    return '';
                }

                return '#' . $this->mUrl->fragment;

            case 'host':
                if ($this->mUrl->host === null) {
                    return '';
                }

                if ($this->mUrl->port === null) {
                    return (string) $this->mUrl->host;
                }

                return (string) $this->mUrl->host . ':' .
                    $this->mUrl->port;

            case 'hostname':
                if ($this->mUrl->host === null) {
                    return '';
                }

                return (string) $this->mUrl->host;

            case 'href':
                return $this->mUrl->serializeURL();

            case 'origin':
                return $this->mUrl->getOrigin()->serializeAsUnicode();

            case 'password':
                return $this->mUrl->password;

            case 'pathname':
                if ($this->mUrl->cannotBeABaseUrl) {
                    return $this->mUrl->path[0];
                }

                if (empty($this->mUrl->path)) {
                    return '';
                }

                return '/' . implode('/', $this->mUrl->path);

            case 'port':
                if ($this->mUrl->port === null) {
                    return '';
                }

                return (string) $this->mUrl->port;

            case 'protocol':
                return $this->mUrl->scheme . ':';

            case 'search':
                if ($this->mUrl->query === null ||
                    $this->mUrl->query === ''
                ) {
                    return '';
                }

                return '?' . $this->mUrl->query;

            case 'searchParams':
                return $this->mSearchParams;

            case 'username':
                return $this->mUrl->username;
        }
    }

    public function __set($aName, $aValue)
    {
        $value = URLUtils::strval($aValue);

        switch ($aName) {
            case 'hash':
                if ($this->mUrl->scheme === 'javascript') {
                    // Terminate these steps
                    return;
                }

                if ($value === '') {
                    $this->mUrl->fragment = null;

                    // Terminate these steps
                    return;
                }

                $input = $value[0] == '#' ? substr($value, 1) : $value;
                $this->mUrl->fragment = '';
                URLParser::parseBasicUrl(
                    $input,
                    null,
                    null,
                    $this->mUrl,
                    URLParser::FRAGMENT_STATE
                );

                break;

            case 'host':
                if ($this->mUrl->cannotBeABaseUrl) {
                    // Terminate these steps
                    return;
                }

                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->mUrl,
                    URLParser::HOST_STATE
                );

                break;

            case 'hostname':
                if ($this->mUrl->cannotBeABaseUrl) {
                    // Terminate these steps
                    return;
                }

                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->mUrl,
                    URLParser::HOSTNAME_STATE
                );

                break;

            case 'href':
                $parsedURL = URLParser::parseBasicUrl($value);

                if ($parsedURL === false) {
                    throw new TypeError($value . ' is not a valid URL.');
                }

                $this->mUrl = $parsedURL;
                $this->mSearchParams->clear();

                if ($this->mUrl->query !== null) {
                    $this->mSearchParams->_mutateList(
                        URLUtils::urlencodedStringParser($this->mUrl->query)
                    );
                }

                break;

            case 'password':
                if ($this->mUrl->host === null ||
                    $this->mUrl->cannotBeABaseUrl
                ) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setPassword($value);

                break;

            case 'pathname':
                if ($this->mUrl->cannotBeABaseUrl) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->path = [];
                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->mUrl,
                    URLParser::PATH_START_STATE
                );

                break;

            case 'port':
                if ($this->mUrl->host === null ||
                    $this->mUrl->cannotBeABaseUrl ||
                    $this->mUrl->scheme === 'file'
                ) {
                    // Terminate these steps
                    return;
                }

                if ($value === '') {
                    $this->mUrl->port = null;
                    return;
                }

                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->mUrl,
                    URLParser::PORT_STATE
                );

                break;

            case 'protocol':
                URLParser::parseBasicUrl(
                    $value . ':',
                    null,
                    null,
                    $this->mUrl,
                    URLParser::SCHEME_START_STATE
                );

                break;

            case 'search':
                if ($value === '') {
                    $this->mUrl->query = null;
                    $this->mSearchParams->clear();

                    return;
                }

                $input = $value[0] == '?' ? substr($value, 1) : $value;
                $this->mUrl->query = '';
                URLParser::parseBasicUrl(
                    $input,
                    null,
                    null,
                    $this->mUrl,
                    URLParser::QUERY_STATE
                );
                $this->mSearchParams->_mutateList(
                    URLUtils::urlencodedStringParser($input)
                );

                break;

            case 'username':
                if ($this->mUrl->host === null ||
                    $this->mUrl->cannotBeABaseUrl
                ) {
                    // Terminate these steps
                    return;
                }

                $this->mUrl->setUsername($value);

                break;
        }
    }

    /**
     * Returns a JSON encoded string without escaping forward slashes. If you
     * need forward slashes to be escaped, pass the URL object to json_encode()
     * instead of calling this method.
     *
     * @see https://url.spec.whatwg.org/#dom-url-tojson
     *
     * @return string
     */
    public function toJSON()
    {
        // Use JSON_UNESCAPED_SLASHES here since JavaScript's JSON.stringify()
        // method does not escape forward slashes by default.
        return json_encode($this->mUrl->serializeURL(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Returns the serialized URL for consumption by json_encode(). To match
     * JavaScript's behavior, you should pass the JSON_UNESCAPED_SLASHES option
     * to json_encode().
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->mUrl->serializeURL();
    }
}
