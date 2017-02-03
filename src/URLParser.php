<?php
namespace phpjs\urls;

abstract class URLParser
{
    const SCHEME_START_STATE = 1;
    const SCHEME_STATE = 2;
    const NO_SCHEME_STATE = 3;
    const SPECIAL_RELATIVE_OR_AUTHORITY_STATE = 4;
    const PATH_OR_AUTHORITY_STATE = 5;
    const RELATIVE_STATE = 6;
    const RELATIVE_SLASH_STATE = 7;
    const SPECIAL_AUTHORITY_SLASHES_STATE = 8;
    const SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE = 9;
    const AUTHORITY_STATE = 10;
    const HOST_STATE = 11;
    const HOSTNAME_STATE = 12;
    const PORT_STATE = 13;
    const FILE_STATE = 14;
    const FILE_SLASH_STATE = 15;
    const FILE_HOST_STATE = 16;
    const PATH_START_STATE = 17;
    const PATH_STATE = 18;
    const CANNOT_BE_A_BASE_URL_PATH_STATE = 19;
    const QUERY_STATE = 20;
    const FRAGMENT_STATE = 21;

    private static $singleDotPathSegment = [
        '.'   => '',
        '%2e' => '',
        '%2E' => ''
    ];
    private static $doubleDotPathSegment = [
        '..'     => '',
        '.%2e'   => '',
        '.%2E'   => '',
        '%2e.'   => '',
        '%2E.'   => '',
        '%2e%2e' => '',
        '%2E%2E' => ''
    ];

    /**
     * Parses a string as a URL. The string can be an absolute URL or a relative
     * URL. If a relative URL is given, a base URL must also be given so that a
     * complete URL can be resolved.  It can also parse individual parts of a
     * URL when the state machine starts in a specific state.
     *
     * @see https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param string $input The URL string that is to be parsed.
     *
     * @param URLRecord|null $base Optional argument that is only needed
     *     if the input is a relative URL.  This represents the base URL, which
     *     in most cases, is the document's URL, it may also be a node's base
     *     URI or whatever base URL you wish to resolve relative URLs against.
     *     Default is null.
     *
     * @param string $encodingOverride Optional argument that overrides the
     *     default encoding. Default is UTF-8.
     *
     * @param URLRecord|null $url Optional argument. This represents an
     *     existing URL object that should be modified based on the input URL
     *     and optional base URL.  Default is null.
     *
     * @param int|null $stateOverride Optional argument. An integer that
     *     determines what state the state machine will begin parsing the input
     *     URL from. Suppling a value for this parameter will override the
     *     default state of SCHEME_START_STATE. Default is null.
     *
     * @return URLRecord|bool Returns a URL object upon successfully parsing
     *     the input or false if parsing input failed.
     */
    public static function parseBasicUrl(
        $input,
        URLRecord $base = null,
        $encodingOverride = null,
        URLRecord $url = null,
        $stateOverride = null
    ) {
        if (!$url) {
            $url = new URLRecord();

            // Remove any leading or trailing C0 control and space characters.
            $input = preg_replace(
                '/^[\x00-\x1F\x20]+|[\x00-\x1F\x20]+$/u',
                '',
                $input,
                -1,
                $count
            );

            // A URL should not contain any leading C0 control and space
            // characters.
            if ($count > 0) {
                // Syntax violation.
            }
        }

        // A URL should not contain any tab or newline characters.
        $input = preg_replace('/[\x09\x0A\x0D]+/u', '', $input, -1, $count);

        if ($count > 0) {
            // Syntax violation
        }

        $state = $stateOverride ?: self::SCHEME_START_STATE;
        $encoding = 'UTF-8';

        // TODO: If encoding override is given, set it to the result of the
        // getting an output encoding algorithm.
        if ($encodingOverride) {
            $encoding = $encodingOverride;
        }

        $buffer = '';
        $atFlag = false;
        $bracketFlag = false;
        $passwordTokenSeenFlag = false;
        $pointer = 0;
        $len = mb_strlen($input, $encoding);

        while (true) {
            $c = mb_substr($input, $pointer++, 1, $encoding);

            switch ($state) {
                case self::SCHEME_START_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_ALPHA, $c)) {
                        $buffer .= strtolower($c);
                        $state = self::SCHEME_STATE;
                    } elseif (!$stateOverride) {
                        $state = self::NO_SCHEME_STATE;
                        $pointer--;
                    } else {
                        // Syntax violation.
                        // Note: This indication of failture is used exclusively
                        // by the Location object's protocol attribute.
                        return false;
                    }

                    break;

                case self::SCHEME_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_ALPHANUMERIC, $c) ||
                        preg_match('/[+\-.]/u', $c)
                    ) {
                        $buffer .= strtolower($c);
                    } elseif ($c === ':') {
                        if ($stateOverride) {
                            $bufferIsSpecialScheme = isset(
                                URLUtils::$specialSchemes[$buffer]
                            );
                            $urlIsSpecial = $url->isSpecial();

                            if (($urlIsSpecial && !$bufferIsSpecialScheme) ||
                                (!$urlIsSpecial && $bufferIsSpecialScheme)
                            ) {
                                // Terminate this algorithm.
                                break 2;
                            }
                        }

                        $url->scheme = $buffer;
                        $buffer = '';

                        if ($stateOverride) {
                            // Terminate this algoritm
                            break 2;
                        }

                        $urlIsSpecial = $url->isSpecial();

                        if ($url->scheme === 'file') {
                            if (mb_strpos(
                                $input,
                                '//',
                                $pointer,
                                $encoding
                            ) !== $pointer) {
                                // Syntax violation
                            }

                            $state = self::FILE_STATE;
                        } elseif ($urlIsSpecial &&
                            $base &&
                            $base->scheme === $url->scheme
                        ) {
                            // This means that base's cannot-be-a-base-URL flag
                            // is unset.
                            $state = self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE;
                        } elseif ($urlIsSpecial) {
                            $state = self::SPECIAL_AUTHORITY_SLASHES_STATE;
                        } elseif (mb_strpos(
                            $input,
                            '/',
                            $pointer,
                            $encoding
                        ) === $pointer) {
                            $state = self::PATH_OR_AUTHORITY_STATE;
                            $pointer++;
                        } else {
                            $url->cannotBeABaseUrl = true;
                            $url->path[] = '';
                            $state = self::CANNOT_BE_A_BASE_URL_PATH_STATE;
                        }
                    } elseif (!$stateOverride) {
                        $buffer = '';
                        $state = self::NO_SCHEME_STATE;

                        // Reset the pointer to poing at the first code point.
                        $pointer = 0;
                    } else {
                        // Syntax violation.
                        // Note: This indication of failure is used exclusively
                        // by the Location object's protocol attribute.
                        // Furthermore, the non-failure termination earlier in
                        // this state is an intentional difference for defining
                        // that attribute.
                        return false;
                    }

                    break;

                case self::NO_SCHEME_STATE:
                    if (!$base || ($base->cannotBeABaseUrl && $c !== '#')) {
                        // Syntax violation. Return failure
                        return false;
                    } elseif ($base->cannotBeABaseUrl && $c === '#') {
                        $url->scheme = $base->scheme;
                        $url->path = $base->path;
                        $url->query = $base->query;
                        $url->fragment = '';
                        $url->cannotBeABaseUrl = true;
                        $state = self::FRAGMENT_STATE;
                    } elseif ($base->scheme !== 'file') {
                        $state = self::RELATIVE_STATE;
                        $pointer--;
                    } else {
                        $state = self::FILE_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE:
                    if ($c === '/' &&
                        mb_strpos($input, '/', $pointer, $encoding) === $pointer
                    ) {
                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                        $pointer++;
                    } else {
                        // Syntax violation
                        $state = self::RELATIVE_STATE;
                        $pointer--;
                    }

                    break;

                case self::PATH_OR_AUTHORITY_STATE:
                    if ($c === '/') {
                        $state = self::AUTHORITY_STATE;
                    } else {
                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::RELATIVE_STATE:
                    $url->scheme = $base->scheme;

                    if ($c === ''/* EOF */) {
                        $url->username = $base->username;
                        $url->password = $base->password;
                        $url->host = $base->host;
                        $url->port = $base->port;
                        $url->path = $base->path;
                        $url->query = $base->query;
                    } elseif ($c === '/') {
                        $state = self::RELATIVE_SLASH_STATE;
                    } elseif ($c === '?') {
                        $url->username = $base->username;
                        $url->password = $base->password;
                        $url->host = $base->host;
                        $url->port = $base->port;
                        $url->path = $base->path;
                        $url->query = '';
                        $state = self::QUERY_STATE;
                    } elseif ($c === '#') {
                        $url->username = $base->username;
                        $url->password = $base->password;
                        $url->host = $base->host;
                        $url->port = $base->port;
                        $url->path = $base->path;
                        $url->query = $base->query;
                        $url->fragment = '';
                        $state = self::FRAGMENT_STATE;
                    } else {
                        if ($url->isSpecial() && $c === '\\') {
                            // Syntax violation
                            $state = self::RELATIVE_SLASH_STATE;
                        } else {
                            $url->username = $base->username;
                            $url->password = $base->password;
                            $url->host = $base->host;
                            $url->port = $base->port;
                            $url->path = $base->path;

                            if (!empty($url->path)) {
                                array_pop($url->path);
                            }

                            $state = self::PATH_STATE;
                            $pointer--;
                        }
                    }

                    break;

                case self::RELATIVE_SLASH_STATE:
                    if ($url->isSpecial() && $c === '/' || $c === '\\') {
                        if ($c === '\\') {
                            // Syntax violation
                        }

                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                    } elseif ($c === '/') {
                        $state = self::AUTHORITY_STATE;
                    } else {
                        $url->username = $base->username;
                        $url->password = $base->password;
                        $url->host = $base->host;
                        $url->port = $base->port;
                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_AUTHORITY_SLASHES_STATE:
                    if ($c === '/' &&
                        mb_strpos($input, '/', $pointer, $encoding) === $pointer
                    ) {
                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                        $pointer++;
                    } else {
                        // Syntax violation
                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE:
                    if ($c !== '/' && $c !== '\\') {
                        $state = self::AUTHORITY_STATE;
                        $pointer--;
                    } else {
                        // Syntax violation
                    }

                    break;

                case self::AUTHORITY_STATE:
                    if ($c === '@') {
                        // Syntax violation

                        if ($atFlag) {
                            $buffer = '%40' . $buffer;
                        }

                        $atFlag = true;
                        $length = mb_strlen($buffer, $encoding);

                        for ($i = 0; $i < $length; $i++) {
                            $codePoint = mb_substr($buffer, $i, 1, $encoding);

                            if ($codePoint === ':' && !$passwordTokenSeenFlag) {
                                $passwordTokenSeenFlag = true;
                                continue;
                            }

                            $encodedCodePoints = URLUtils::utf8PercentEncode(
                                $codePoint,
                                URLUtils::ENCODE_SET_USERINFO
                            );

                            if ($passwordTokenSeenFlag) {
                                $url->password .= $encodedCodePoints;
                            } else {
                                $url->username .= $encodedCodePoints;
                            }
                        }

                        $buffer = '';
                    } elseif (($c === ''/* EOF */ ||
                        $c === '/' ||
                        $c === '?' ||
                        $c === '#') ||
                        ($url->isSpecial() && $c === '\\')
                    ) {
                        if ($atFlag && $buffer === '') {
                            // Syntax violation
                            return false;
                        }

                        $pointer -= mb_strlen($buffer, $encoding) + 1;
                        $buffer = '';
                        $state = self::HOST_STATE;
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::HOST_STATE:
                case self::HOSTNAME_STATE:
                    if ($c === ':' && !$bracketFlag) {
                        if ($buffer === '') {
                            // Syntax violation. Return failure
                            return false;
                        }

                        $host = URLUtils::parseUrlHost(
                            $buffer,
                            $url->isSpecial()
                        );

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->host = $host;
                        $buffer = '';
                        $state = self::PORT_STATE;

                        if ($stateOverride === self::HOSTNAME_STATE) {
                            // Terminate this algorithm
                            break 2;
                        }
                    } elseif (($c === ''/* EOF */ ||
                        $c === '/' ||
                        $c === '?' ||
                        $c === '#') ||
                        ($url->isSpecial() && $c === '\\')
                    ) {
                        $pointer--;

                        if ($url->isSpecial() && $buffer === '') {
                            // Syntax violation. Return failure
                            return false;
                        }

                        $host = URLUtils::parseUrlHost(
                            $buffer,
                            $url->isSpecial()
                        );

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->host = $host;
                        $buffer = '';
                        $state = self::PATH_START_STATE;

                        if ($stateOverride) {
                            // Terminate this algorithm
                            break 2;
                        }
                    } else {
                        if ($c === '[') {
                            $bracketFlag = true;
                        } elseif ($c === ']') {
                            $bracketFlag = false;
                        }

                        $buffer .= $c;
                    }

                    break;

                case self::PORT_STATE:
                    if (ctype_digit($c)) {
                        $buffer .= $c;
                    } elseif (($c === ''/* EOF */ ||
                        $c === '/' ||
                        $c === '?' ||
                        $c === '#') ||
                        ($url->isSpecial() && $c === '\\') ||
                        $stateOverride
                    ) {
                        if ($buffer !== '') {
                            $port = intval($buffer, 10);

                            if ($port > pow(2, 16) - 1) {
                                // Syntax violation. Return failure.
                                return false;
                            }

                            $isSpecial = $url->isSpecial();

                            if ($isSpecial) {
                                $defaultPort = URLUtils::$specialSchemes[
                                    $url->scheme
                                ];
                            }

                            if ($isSpecial && $defaultPort === $port) {
                                $url->port = null;
                            } else {
                                $url->port = $port;
                            }

                            $buffer = '';
                        }

                        if ($stateOverride) {
                            // Terminate this algorithm
                            break 2;
                        }

                        $state = self::PATH_START_STATE;
                        $pointer--;
                    } else {
                        // Syntax violation. Return failure.
                        return false;
                    }

                    break;

                case self::FILE_STATE:
                    $url->scheme = 'file';

                    if ($c === ''/* EOF */) {
                        if ($base && $base->scheme === 'file') {
                            $url->host = $base->host;
                            $url->path = $base->path;
                            $url->query = $base->query;
                        }
                    } elseif ($c === '/' || $c === '\\') {
                        if ($c === '\\') {
                            // Syntax violation
                        }

                        $state = self::FILE_SLASH_STATE;
                    } elseif ($c === '?') {
                        if ($base && $base->scheme === 'file') {
                            $url->host = $base->host;
                            $url->path = $base->path;
                            $url->query = '';
                            $state = self::QUERY_STATE;
                        }
                    } elseif ($c === '#') {
                        if ($base && $base->scheme === 'file') {
                            $url->host = $base->host;
                            $url->path = $base->path;
                            $url->query = $base->query;
                            $url->fragment = '';
                            $state = self::FRAGMENT_STATE;
                        }
                    } else {
                        // Platform-independent Windows drive letter quirk
                        $shouldPopPath = $base &&
                            $base->scheme === 'file' &&
                            // If c and the first code point of remaining are
                            // not a Windows drive letter
                            (!preg_match(
                                URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                                $c . mb_substr($input, $pointer, 1, $encoding)
                            ) ||
                            // If remaining consists of 1 code point
                            mb_strlen(
                                mb_substr(
                                    $input,
                                    $pointer,
                                    null,
                                    $encoding
                                ),
                                $encoding
                            ) == 1 ||
                            // If remaining's second code point is not /, \, ?,
                            // or #.
                            !preg_match(
                                '/[\/\\\\?#]/',
                                mb_substr(
                                    $input,
                                    $pointer + 1,
                                    1,
                                    $encoding
                                )
                            )
                        );

                        if ($shouldPopPath) {
                            $url->host = $base->host;
                            $url->path = $base->path;
                            $url->shortenPath();
                        } elseif ($base && $base->scheme === 'file') {
                            // Syntax violation
                        }

                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::FILE_SLASH_STATE:
                    if ($c === '/' || $c === '\\') {
                        if ($c === '\\') {
                            // Syntax violation
                        }

                        $state = self::FILE_HOST_STATE;
                    } else {
                        if ($base &&
                            $base->scheme === 'file' &&
                            preg_match(
                                URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER,
                                $base->path[0]
                            )
                        ) {
                            // This is a (platform-independent) Windows drive
                            // letter quirk. Both url’s and base’s host are null
                            // under these conditions and therefore not copied.
                            $url->path[] = $base->path[0];
                        }

                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::FILE_HOST_STATE:
                    if ($c === ''/* EOF */ ||
                        $c === '/' ||
                        $c === '\\' ||
                        $c === '?' ||
                        $c === '#'
                    ) {
                        $pointer--;

                        if (preg_match(
                            URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                            $buffer
                        )) {
                            // This is a (platform-independent) Windows drive
                            // letter quirk. buffer is not reset here and
                            // instead used in the path state.
                            // Syntax violation
                            $state = self::PATH_STATE;
                        } elseif ($buffer === '') {
                            $state = self::PATH_START_STATE;
                        } else {
                            $host = HostFactory::parse($buffer);

                            if ($host === false) {
                                // Return failure
                                return false;
                            }

                            if ($host !== 'localhost') {
                                $url->host = $host;
                            }

                            $buffer = '';
                            $state = self::PATH_START_STATE;
                        }
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::PATH_START_STATE:
                    if ($url->isSpecial()) {
                        if ($c === '\\') {
                            // Syntax violation
                        }

                        $state = self::PATH_STATE;

                        if ($c !== '/' && $c !== '\\') {
                            $pointer--;
                        }
                    } elseif (!$stateOverride && $c === '?') {
                        $url->query = '';
                        $state = self::QUERY_STATE;
                    } elseif (!$stateOverride && $c === '#') {
                        $url->fragment = '';
                        $state = self::FRAGMENT_STATE;
                    } elseif ($c !== '') {
                        $state = self::PATH_STATE;

                        if ($c !== '/') {
                            $pointer--;
                        }
                    }

                    break;

                case self::PATH_STATE:
                    if ($c === ''/* EOF */ ||
                        $c === '/' ||
                        ($url->isSpecial() && $c === '\\') ||
                        (!$stateOverride && ($c === '?' || $c === '#'))
                    ) {
                        $urlIsSpecial = $url->isSpecial();

                        if ($urlIsSpecial && $c === '\\') {
                            // Syntax violation
                        }

                        if (isset(self::$doubleDotPathSegment[$buffer])) {
                            $url->shortenPath();

                            if ($c !== '/' && !($urlIsSpecial && $c === '\\')) {
                                $url->path[] = '';
                            }
                        } elseif (isset(self::$singleDotPathSegment[$buffer]) &&
                            $c !== '/' &&
                            !($url->isSpecial() && $c === '\\')
                        ) {
                            $url->path[] = '';
                        } elseif (!isset(
                            self::$singleDotPathSegment[$buffer]
                        )) {
                            if ($url->scheme === 'file' &&
                                empty($url->path) &&
                                preg_match(
                                    URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                                    $buffer
                                )
                            ) {
                                if ($url->host !== null) {
                                    // Syntax violation
                                }

                                $url->host = null;
                                // This is a (platform-independent) Windows
                                // drive letter quirk.
                                $buffer = mb_substr($buffer, 0, 1, $encoding) .
                                    ':' .
                                    mb_substr($buffer, 2, null, $encoding);
                            }

                            $url->path[] = $buffer;
                        }

                        $buffer = '';

                        if ($c === '?') {
                            $url->query = '';
                            $state = self::QUERY_STATE;
                        } elseif ($c === '#') {
                            $url->fragment = '';
                            $state = self::FRAGMENT_STATE;
                        }
                    } else {
                        if (!preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) &&
                            $c !== '%'
                        ) {
                            // Syntax violation
                        }

                        $remaining = mb_substr(
                            $input,
                            $pointer,
                            2,
                            $encoding
                        );

                        if ($c === '%' && !ctype_xdigit($remaining)) {
                            // Syntax violation
                        }

                        $buffer .= URLUtils::utf8PercentEncode(
                            $c,
                            URLUtils::ENCODE_SET_DEFAULT
                        );
                    }

                    break;

                case self::CANNOT_BE_A_BASE_URL_PATH_STATE:
                    if ($c === '?') {
                        $url->query = '';
                        $state = self::QUERY_STATE;
                    } elseif ($c === '#') {
                        $url->fragment = '';
                        $state = self::FRAGMENT_STATE;
                    } else {
                        if ($c !== ''/* EOF */ &&
                            !preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) &&
                            $c !== '%'
                        ) {
                            // Syntax violation
                        }

                        if ($c === '%' &&
                            !ctype_xdigit(
                                mb_substr($input, $pointer, 2, $encoding)
                            )
                        ) {
                            // Syntax violation
                        }

                        if ($c !== ''/* EOF */) {
                            if (!empty($url->path)) {
                                $url->path[0] .= URLUtils::utf8PercentEncode(
                                    $c
                                );
                            }
                        }
                    }

                    break;

                case self::QUERY_STATE:
                    if ($c === ''/* EOF */ ||
                        (!$stateOverride && $c === '#')
                    ) {
                        $oldEncoding = $encoding;

                        if (!$url->isSpecial() ||
                            $url->scheme === 'ws' ||
                            $url->scheme === 'wss'
                        ) {
                            $encoding = 'utf-8';
                        }

                        if ($encoding !== $oldEncoding) {
                            $buffer = mb_convert_encoding(
                                $buffer,
                                $encoding,
                                $oldEncoding
                            );
                        }

                        $length = strlen($buffer);

                        for ($i = 0; $i < $length; $i++) {
                            if ($buffer[$i] < "\x21" ||
                                $buffer[$i] > "\x7E" ||
                                $buffer[$i] === "\x22" ||
                                $buffer[$i] === "\x23" ||
                                $buffer[$i] === "\x3C" ||
                                $buffer[$i] === "\x3E"
                            ) {
                                $url->query .= rawurlencode($buffer[$i]);
                            } else {
                                $url->query .= $buffer[$i];
                            }
                        }

                        $buffer = '';

                        if ($c === '#') {
                            $url->fragment = '';
                            $state = self::FRAGMENT_STATE;
                        }
                    } else {
                        if (!preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) &&
                            $c !== '%'
                        ) {
                            // Syntax violation
                        }

                        if ($c === '%' &&
                            !ctype_xdigit(
                                mb_substr($input, $pointer, 2, $encoding)
                            )
                        ) {
                            // Syntax violation
                        }

                        $buffer .= $c;
                    }

                    break;

                case self::FRAGMENT_STATE:
                    if ($c === ''/* EOF */) {
                        // Do nothing
                    } elseif ($c === "\0") {
                        // Syntax violation
                    } else {
                        if (!preg_match(URLUtils::REGEX_URL_CODE_POINTS, $c) &&
                            $c !== '%'
                        ) {
                            // Syntax violation
                        }

                        if ($c === '%' &&
                            !ctype_xdigit(
                                mb_substr($input, $pointer, 2, $encoding)
                            )
                        ) {
                            // Syntax violation
                        }

                        $url->fragment .= URLUtils::utf8PercentEncode($c);
                    }

                    break;
            }

            if ($pointer > $len) {
                break;
            }
        }

        return $url;
    }

    public static function parseUrl(
        $input,
        URLRecord $base = null,
        $encodingOverride = null
    ) {
        $url = self::parseBasicUrl($input, $base, $encodingOverride);

        if ($url === false) {
            return false;
        }

        if ($url->scheme !== 'blob') {
            return $url;
        }

        // TODO: If the first string in url’s path is not in the blob URL store,
        // return url

        // TODO: Set url’s object to a structured clone of the entry in the blob
        // URL store corresponding to the first string in url’s path

        return $url;
    }
}
