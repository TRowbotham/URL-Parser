<?php

declare(strict_types=1);

namespace Rowbot\URL;

use InvalidArgumentException;
use JsonSerializable;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\Exception\TypeError;
use Rowbot\URL\IDLStringPreprocessor;
use Rowbot\URL\State\FragmentState;
use Rowbot\URL\State\HostnameState;
use Rowbot\URL\State\HostState;
use Rowbot\URL\State\PathStartState;
use Rowbot\URL\State\PortState;
use Rowbot\URL\State\QueryState;
use Rowbot\URL\State\SchemeStartState;
use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\String\Utf8String;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;

/**
 * Represents a URL that can be manipulated.
 *
 * @see https://url.spec.whatwg.org/#api
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URL
 *
 * @property string                      $href
 * @property string                      $origin
 * @property string                      $protocol
 * @property string                      $username
 * @property string                      $password
 * @property string                      $host
 * @property string                      $hostname
 * @property string                      $port
 * @property string                      $pathname
 * @property string                      $search
 * @property \Rowbot\URL\URLSearchParams $searchParams
 * @property string                      $hash
 */
class URL implements JsonSerializable
{
    use URLFormEncoded;

    /**
     * @var \Rowbot\URL\URLSearchParams
     */
    private $queryObject;

    /**
     * @var \Rowbot\URL\URLRecord
     */
    private $url;

    /**
     * @throws \Rowbot\URL\Exception\TypeError
     */
    public function __construct(string $url, string $base = null)
    {
        $parsedBase = null;
        $parser = new BasicURLParser();
        $idl = new IDLStringPreprocessor();

        if ($base !== null) {
            $input = new Utf8String($idl->process($base));
            $parsedBase = $parser->parse($input);

            if ($parsedBase === false) {
                throw new TypeError($base . ' is not a valid URL.');
            }
        }

        $input = new Utf8String($idl->process($url));
        $parsedURL = $parser->parse($input, $parsedBase);

        if ($parsedURL === false) {
            throw new TypeError($url . ' is not a valid URL.');
        }

        $this->url = $parsedURL;
        $query = $this->url->query ?? '';
        $this->queryObject = URLSearchParams::create($query, $parsedURL);
    }

    public function __clone()
    {
        $this->url = clone $this->url;
        $this->queryObject = clone $this->queryObject;
        $this->queryObject->setUrl($this->url);
    }

    /**
     * @return string|\Rowbot\URL\URLSearchParams
     *
     * @throws \InvalidArgumentException When an invalid $name value is passed.
     */
    public function __get(string $name)
    {
        if ($name === 'hash') {
            if ($this->url->fragment === null || $this->url->fragment === '') {
                return '';
            }

            return '#' . $this->url->fragment;
        }

        if ($name === 'host') {
            if ($this->url->host->isNull()) {
                return '';
            }

            $serializer = $this->url->host->getSerializer();

            if ($this->url->port === null) {
                return $serializer->toFormattedString();
            }

            return $serializer->toFormattedString() . ':' . $this->url->port;
        }

        if ($name === 'hostname') {
            if ($this->url->host->isNull()) {
                return '';
            }

            return $this->url->host->getSerializer()->toFormattedString();
        }

        if ($name === 'href') {
            return $this->url->serializeURL();
        }

        if ($name === 'origin') {
            return (string) $this->url->getOrigin();
        }

        if ($name === 'password') {
            return $this->url->password;
        }

        if ($name === 'pathname') {
            if ($this->url->cannotBeABaseUrl) {
                return (string) $this->url->path->first();
            }

            if ($this->url->path->isEmpty()) {
                return '';
            }

            return '/' . $this->url->path;
        }

        if ($name === 'port') {
            if ($this->url->port === null) {
                return '';
            }

            return (string) $this->url->port;
        }

        if ($name === 'protocol') {
            return $this->url->scheme . ':';
        }

        if ($name === 'search') {
            if ($this->url->query === null || $this->url->query === '') {
                return '';
            }

            return '?' . $this->url->query;
        }

        if ($name === 'searchParams') {
            return $this->queryObject;
        }

        if ($name === 'username') {
            return $this->url->username;
        }

        throw new InvalidArgumentException($name . ' is not a valid property.');
    }

    /**
     * @throws \InvalidArgumentException       When an invalid $name or $value value is passed.
     * @throws \Rowbot\URL\Exception\TypeError Only when trying to set URL::$searchParams
     */
    public function __set(string $name, string $value): void
    {
        if ($name === 'searchParams') {
            throw new TypeError();
        }

        $idl = new IDLStringPreprocessor();
        $input = new Utf8String($idl->process($value));
        $parser = new BasicURLParser();

        if ($name === 'hash') {
            if ($input->isEmpty()) {
                $this->url->fragment = null;

                // Terminate these steps
                return;
            }

            if ($input->startsWith('#')) {
                $input = $input->substr(1);
            }

            $this->url->fragment = '';
            $parser->parse($input, null, null, $this->url, new FragmentState());
        } elseif ($name === 'host') {
            if ($this->url->cannotBeABaseUrl) {
                // Terminate these steps
                return;
            }

            $parser->parse($input, null, null, $this->url, new HostState());
        } elseif ($name === 'hostname') {
            if ($this->url->cannotBeABaseUrl) {
                // Terminate these steps
                return;
            }

            $parser->parse($input, null, null, $this->url, new HostnameState());
        } elseif ($name === 'href') {
            $parsedURL = $parser->parse($input);

            if ($parsedURL === false) {
                throw new TypeError($value . ' is not a valid URL.');
            }

            $this->url = $parsedURL;
            $this->queryObject->setUrl($this->url);
            $this->queryObject->clear();

            if ($this->url->query !== null) {
                $this->queryObject->modify($this->urldecodeString($this->url->query));
            }
        } elseif ($name === 'password') {
            if ($this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            $this->setUrlPassword($input);
        } elseif ($name === 'pathname') {
            if ($this->url->cannotBeABaseUrl) {
                // Terminate these steps
                return;
            }

            $this->url->path = new PathList();
            $parser->parse($input, null, null, $this->url, new PathStartState());
        } elseif ($name === 'port') {
            if ($this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            if ($value === '') {
                $this->url->port = null;

                return;
            }

            $parser->parse($input, null, null, $this->url, new PortState());
        } elseif ($name === 'protocol') {
            $parser->parse($input->append(':'), null, null, $this->url, new SchemeStartState());
        } elseif ($name === 'search') {
            if ($value === '') {
                $this->url->query = null;
                $this->queryObject->clear();

                return;
            }

            if ($input->startsWith('?')) {
                $input = $input->substr(1);
            }

            $this->url->query = '';
            $parser->parse($input, null, null, $this->url, new QueryState());
            $this->queryObject->modify($this->urldecodeString((string) $input));
        } elseif ($name === 'username') {
            if ($this->url->cannotHaveUsernamePasswordPort()) {
                return;
            }

            $this->setUrlUsername($input);
        } else {
            throw new InvalidArgumentException($name . ' is not a valid property.');
        }
    }

    /**
     * @see https://url.spec.whatwg.org/#set-the-password
     */
    private function setUrlPassword(USVStringInterface $input): void
    {
        $this->url->password = '';

        foreach ($input as $codePoint) {
            $this->url->password .= CodePoint::utf8PercentEncode(
                $codePoint,
                CodePoint::USERINFO_PERCENT_ENCODE_SET
            );
        }
    }

    /**
     * @see https://url.spec.whatwg.org/#set-the-username
     */
    private function setUrlUsername(USVStringInterface $input): void
    {
        $this->url->username = '';

        foreach ($input as $codePoint) {
            $this->url->username .= CodePoint::utf8PercentEncode(
                $codePoint,
                CodePoint::USERINFO_PERCENT_ENCODE_SET
            );
        }
    }

    public function __toString(): string
    {
        return $this->url->serializeURL();
    }

    public function toString(): string
    {
        return $this->url->serializeURL();
    }

    /**
     * Returns a JSON encoded string without escaping forward slashes. If you
     * need forward slashes to be escaped, pass the URL object to json_encode()
     * instead of calling this method.
     *
     * @see https://url.spec.whatwg.org/#dom-url-tojson
     */
    public function toJSON(): string
    {
        // Use JSON_UNESCAPED_SLASHES here since JavaScript's JSON.stringify()
        // method does not escape forward slashes by default.
        return json_encode($this->url->serializeURL(), JSON_UNESCAPED_SLASHES);
    }

    /**
     * Returns the serialized URL for consumption by json_encode(). To match
     * JavaScript's behavior, you should pass the JSON_UNESCAPED_SLASHES option
     * to json_encode().
     */
    public function jsonSerialize(): string
    {
        return $this->url->serializeURL();
    }
}
