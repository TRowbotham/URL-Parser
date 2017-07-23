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
    private $queryObject;
    private $url;

    public function __construct($url, $base = null)
    {
        $this->queryObject = null;
        $this->url = null;
        $parsedBase = null;

        if ($base) {
            $parsedBase = URLParser::parseBasicUrl($base);

            if ($parsedBase === false) {
                throw new TypeError($base . ' is not a valid URL.');
                return;
            }
        }

        $parsedURL = URLParser::parseBasicUrl($url, $parsedBase);

        if ($parsedURL === false) {
            throw new TypeError($url . ' is not a valid URL.');
            return;
        }

        $this->url = $parsedURL;
        $query = $this->url->query ?: '';
        $this->queryObject = URLSearchParams::create($query, $parsedURL);
    }

    public function __clone()
    {
        $this->url = clone $this->url;
        $this->queryObject = clone $this->queryObject;
        $this->queryObject->setUrl($this->url);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'hash':
                if ($this->url->fragment === null ||
                    $this->url->fragment === ''
                ) {
                    return '';
                }

                return '#' . $this->url->fragment;

            case 'host':
                if ($this->url->host->isNull()) {
                    return '';
                }

                if ($this->url->port === null) {
                    return (string) $this->url->host;
                }

                return (string) $this->url->host . ':' .
                    $this->url->port;

            case 'hostname':
                if ($this->url->host->isNull()) {
                    return '';
                }

                return (string) $this->url->host;

            case 'href':
                return $this->url->serializeURL();

            case 'origin':
                return (string) $this->url->getOrigin();

            case 'password':
                return $this->url->password;

            case 'pathname':
                if ($this->url->cannotBeABaseUrl) {
                    return $this->url->path[0];
                }

                if (empty($this->url->path)) {
                    return '';
                }

                return '/' . implode('/', $this->url->path);

            case 'port':
                if ($this->url->port === null) {
                    return '';
                }

                return (string) $this->url->port;

            case 'protocol':
                return $this->url->scheme . ':';

            case 'search':
                if ($this->url->query === null ||
                    $this->url->query === ''
                ) {
                    return '';
                }

                return '?' . $this->url->query;

            case 'searchParams':
                return $this->queryObject;

            case 'username':
                return $this->url->username;
        }
    }

    public function __set($name, $value)
    {
        $value = URLUtils::strval($value);

        switch ($name) {
            case 'hash':
                if ($value === '') {
                    $this->url->fragment = null;

                    // Terminate these steps
                    return;
                }

                $input = $value[0] == '#' ? substr($value, 1) : $value;
                $this->url->fragment = '';
                URLParser::parseBasicUrl(
                    $input,
                    null,
                    null,
                    $this->url,
                    URLParser::FRAGMENT_STATE
                );

                break;

            case 'host':
                if ($this->url->cannotBeABaseUrl) {
                    // Terminate these steps
                    return;
                }

                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->url,
                    URLParser::HOST_STATE
                );

                break;

            case 'hostname':
                if ($this->url->cannotBeABaseUrl) {
                    // Terminate these steps
                    return;
                }

                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->url,
                    URLParser::HOSTNAME_STATE
                );

                break;

            case 'href':
                $parsedURL = URLParser::parseBasicUrl($value);

                if ($parsedURL === false) {
                    throw new TypeError($value . ' is not a valid URL.');
                    return;
                }

                $this->url = $parsedURL;
                $this->queryObject->clear();

                if ($this->url->query !== null) {
                    $this->queryObject->modify(
                        URLUtils::urlencodedStringParser($this->url->query)
                    );
                }

                break;

            case 'password':
                if ($this->url->cannotHaveUsernamePasswordPort()) {
                    return;
                }

                $this->url->setPassword($value);

                break;

            case 'pathname':
                if ($this->url->cannotBeABaseUrl) {
                    // Terminate these steps
                    return;
                }

                $this->url->path = [];
                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->url,
                    URLParser::PATH_START_STATE
                );

                break;

            case 'port':
                if ($this->url->cannotHaveUsernamePasswordPort()) {
                    return;
                }

                if ($value === '') {
                    $this->url->port = null;
                    return;
                }

                URLParser::parseBasicUrl(
                    $value,
                    null,
                    null,
                    $this->url,
                    URLParser::PORT_STATE
                );

                break;

            case 'protocol':
                URLParser::parseBasicUrl(
                    $value . ':',
                    null,
                    null,
                    $this->url,
                    URLParser::SCHEME_START_STATE
                );

                break;

            case 'search':
                if ($value === '') {
                    $this->url->query = null;
                    $this->queryObject->clear();

                    return;
                }

                $input = $value[0] == '?' ? substr($value, 1) : $value;
                $this->url->query = '';
                URLParser::parseBasicUrl(
                    $input,
                    null,
                    null,
                    $this->url,
                    URLParser::QUERY_STATE
                );
                $this->queryObject->modify(
                    URLUtils::urlencodedStringParser($input)
                );

                break;

            case 'username':
                if ($this->url->cannotHaveUsernamePasswordPort()) {
                    return;
                }

                $this->url->setUsername($value);

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
        return json_encode($this->url->serializeURL(), JSON_UNESCAPED_SLASHES);
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
        return $this->url->serializeURL();
    }
}
