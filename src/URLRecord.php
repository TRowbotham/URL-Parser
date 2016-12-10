<?php
namespace phpjs\urls;

class URLRecord
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

    const FLAG_CANNOT_BE_A_BASE_URL = 1;

    private static $singleDotPathSegment = array(
        '.'   => '',
        '%2e' => '',
        '%2E' => ''
    );
    private static $doubleDotPathSegment = array(
        '..'     => '',
        '.%2e'   => '',
        '.%2E'   => '',
        '%2e.'   => '',
        '%2E.'   => '',
        '%2e%2e' => '',
        '%2E%2E' => ''
    );

    private $mFlags;
    private $mFragment;
    private $mHost;
    private $mPassword;
    private $mPath;
    private $mPort;
    private $mQuery;
    private $mScheme;
    private $mUsername;

    public function __construct()
    {
        $this->mFlags = 0;
        $this->mFragment = null;
        $this->mHost = null;
        $this->mPassword = null;
        $this->mPath = new \SplDoublyLinkedList();
        $this->mPort = null;
        $this->mQuery = null;
        $this->mScheme = '';
        $this->mUsername = '';
    }

    public function __destruct()
    {
        $this->mPath = null;
    }

    /**
     * Parses a string as a URL. The string can be an absolute URL or a relative
     * URL. If a relative URL is give, a base URL must also be given so that a
     * complete URL can be resolved.  It can also parse individual parts of a
     * URL when the state machine starts in a specific state.
     *
     * @see https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param string $aInput The URL string that is to be parsed.
     *
     * @param URLRecord|null $aBase Optional argument that is only needed
     *     if the input is a relative URL.  This represents the base URL, which
     *     in most cases, is the document's URL, it may also be a node's base
     *     URI or whatever base URL you wish to resolve relative URLs against.
     *     Default is null.
     *
     * @param string $aEncodingOverride Optional argument that overrides the
     *     default encoding. Default is UTF-8.
     *
     * @param URLRecord|null $aUrl Optional argument. This represents an
     *     existing URL object that should be modified based on the input URL
     *     and optional base URL.  Default is null.
     *
     * @param int|null $aStateOverride Optional argument. An integer that
     *     determineswhat state the state machine will begin parsing the input
     *     URL from. Suppling a value for this parameter will override the
     *     default state of SCHEME_START_STATE. Default is null.
     *
     * @return URLRecord|bool Returns a URL object upon successfully parsing
     *     the input or false if parsing input failed.
     */
    public static function basicURLParser(
        $aInput,
        URLRecord $aBase = null,
        $aEncodingOverride = null,
        URLRecord $aUrl = null,
        $aStateOverride = null
    ) {
        $url = $aUrl;
        $input = $aInput;

        if (!$aUrl) {
            $url = new URLRecord();

            // Remove any leading or trailing C0 control and space characters.
            $input = preg_replace(
                '/^[\x00-\x1F\x20]+|[\x00-\x1F\x20]+$/',
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
        if (preg_match('/[\x09\x0A\x0D]+/', $input)) {
            // Syntax violation

            // Remove all tab and newline characters.
            $input = str_replace(["\t", "\n", "\r"], '', $input);
        }

        $state = $aStateOverride ?: self::SCHEME_START_STATE;
        $base = $aBase;
        // TODO: If encoding override is given, set it to the result of the
        // getting an output encoding algorithm.
        $encoding = $aEncodingOverride ?: 'utf-8';
        $buffer = '';
        $flag_at = false;
        $flag_array = false;
        $len = mb_strlen($input, $encoding);

        for ($pointer = 0; $pointer <= $len; $pointer++) {
            $c = mb_substr($input, $pointer, 1, $encoding);

            switch ($state) {
                case self::SCHEME_START_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_ALPHA, $c)) {
                        $buffer .= strtolower($c);
                        $state = self::SCHEME_STATE;
                    } elseif (!$aStateOverride) {
                        $state = self::NO_SCHEME_STATE;
                        $pointer--;
                    } else {
                        // Syntax violation. Terminate this algorithm.
                        break 2;
                    }

                    break;

                case self::SCHEME_STATE:
                    if (preg_match(URLUtils::REGEX_ASCII_ALPHANUMERIC, $c) ||
                        preg_match('/[+\-.]/', $c)
                    ) {
                        $buffer .= strtolower($c);
                    } elseif ($c === ':') {
                        if ($aStateOverride) {
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

                        $url->mScheme = $buffer;
                        $buffer = '';

                        if ($aStateOverride) {
                            // Terminate this algoritm
                            break 2;
                        }

                        $offset = $pointer + 1;
                        $urlIsSpecial = $url->isSpecial();

                        if ($url->mScheme === 'file') {
                            if (mb_strpos(
                                $input,
                                '//',
                                $offset,
                                $encoding
                            ) !== $offset) {
                                // Syntax violation
                            }

                            $state = self::FILE_STATE;
                        } elseif ($urlIsSpecial &&
                            $base &&
                            $base->mScheme === $url->mScheme
                        ) {
                            // This means that base's cannot-be-a-base-URL flag
                            // is unset.
                            $state = self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE;
                        } elseif ($urlIsSpecial) {
                            $state = self::SPECIAL_AUTHORITY_SLASHES_STATE;
                        } elseif (mb_strpos(
                            $input,
                            '/',
                            $offset,
                            $encoding
                        ) === $offset) {
                            $state = self::PATH_OR_AUTHORITY_STATE;
                            $pointer++;
                        } else {
                            $url->mFlags |=
                                URLRecord::FLAG_CANNOT_BE_A_BASE_URL;
                            $url->mPath->push('');
                            $state = self::CANNOT_BE_A_BASE_URL_PATH_STATE;
                        }
                    } elseif (!$aStateOverride) {
                        $buffer = '';
                        $state = self::NO_SCHEME_STATE;

                        // Reset the pointer to poing at the first code point.
                        // The pointer needs to be set to -1 to compensate for
                        // the loop incrementing pointer after this iteration.
                        $pointer = -1;
                    } else {
                        // Syntax violation. Terminate this algorithm.
                        break 2;
                    }

                    break;

                case self::NO_SCHEME_STATE:
                    $cannotBeBase = $base &&
                        $base->mFlags & URLRecord::FLAG_CANNOT_BE_A_BASE_URL;

                    if (!$base || ($cannotBeBase && $c !== '#')) {
                        // Syntax violation. Return failure
                        return false;
                    } elseif ($cannotBeBase && $c === '#') {
                        $url->mScheme = $base->mScheme;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = $base->mQuery;
                        $url->mFragment = '';
                        $url->mFlags |= URLRecord::FLAG_CANNOT_BE_A_BASE_URL;
                        $state = self::FRAGMENT_STATE;
                    } elseif ($base->mScheme !== 'file') {
                        $state = self::RELATIVE_STATE;
                        $pointer--;
                    } else {
                        $state = self::FILE_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_RELATIVE_OR_AUTHORITY_STATE:
                    $offset = $pointer + 1;

                    if ($c === '/' &&
                        mb_strpos($input, '/', $offset, $encoding) === $offset
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
                    $url->mScheme = $base->mScheme;

                    if ($c === ''/* EOF */) {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = $base->mQuery;
                    } elseif ($c === '/') {
                        $state = self::RELATIVE_SLASH_STATE;
                    } elseif ($c === '?') {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = '';
                        $state = self::QUERY_STATE;
                    } elseif ($c === '#') {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $url->mPath = clone $base->mPath;
                        $url->mQuery = $base->mQuery;
                        $url->mFragment = '';
                        $state = self::FRAGMENT_STATE;
                    } else {
                        if ($url->isSpecial() && $c === '\\') {
                            // Syntax violation
                            $state = self::RELATIVE_SLASH_STATE;
                        } else {
                            $url->mUsername = $base->mUsername;
                            $url->mPassword = $base->mPassword;
                            $url->mHost = $base->mHost;
                            $url->mPort = $base->mPort;
                            $url->mPath = clone $base->mPath;

                            if (!$url->mPath->isEmpty()) {
                                $url->mPath->pop();
                            }

                            $state = self::PATH_STATE;
                            $pointer--;
                        }
                    }

                    break;

                case self::RELATIVE_SLASH_STATE:
                    if ($c === '/' || ($url->isSpecial() && $c === '\\')) {
                        if ($c === '\\') {
                            // Syntax violation
                        }

                        $state = self::SPECIAL_AUTHORITY_IGNORE_SLASHES_STATE;
                    } else {
                        $url->mUsername = $base->mUsername;
                        $url->mPassword = $base->mPassword;
                        $url->mHost = $base->mHost;
                        $url->mPort = $base->mPort;
                        $state = self::PATH_STATE;
                        $pointer--;
                    }

                    break;

                case self::SPECIAL_AUTHORITY_SLASHES_STATE:
                    $offset = $pointer + 1;

                    if ($c === '/' &&
                        mb_strpos($input, '/', $offset, $encoding) === $offset
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

                        if ($flag_at) {
                            $buffer = '%40' . $buffer;
                        }

                        $flag_at = true;
                        $length = mb_strlen($buffer, $encoding);

                        for ($i = 0; $i < $length; $i++) {
                            $codePoint = mb_substr($buffer, $i, 1, $encoding);

                            if ($codePoint === ':' &&
                                $url->mPassword === null
                            ) {
                                $url->mPassword = '';
                                continue;
                            }

                            $encodedCodePoints = URLUtils::utf8PercentEncode(
                                $codePoint,
                                URLUtils::ENCODE_SET_USERINFO
                            );

                            if ($url->mPassword !== null) {
                                $url->mPassword .= $encodedCodePoints;
                            } else {
                                $url->mUsername .= $encodedCodePoints;
                            }
                        }

                        $buffer = '';
                    } elseif (($c === ''/* EOF */ ||
                        $c === '/' ||
                        $c === '?' ||
                        $c === '#') ||
                        ($url->isSpecial() && $c === '\\')
                    ) {
                        $pointer -= mb_strlen($buffer, $encoding) + 1;
                        $buffer = '';
                        $state = self::HOST_STATE;
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::HOST_STATE:
                case self::HOSTNAME_STATE:
                    if ($c === ':' && !$flag_array) {
                        if ($url->isSpecial() && $buffer === '') {
                            // Return failure
                            return false;
                        }

                        $host = HostFactory::parse($buffer);

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->mHost = $host;
                        $buffer = '';
                        $state = self::PORT_STATE;

                        if ($aStateOverride === self::HOSTNAME_STATE) {
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
                            // Return failure
                            return false;
                        }

                        $host = HostFactory::parse($buffer);

                        if ($host === false) {
                            // Return failure
                            return false;
                        }

                        $url->mHost = $host;
                        $buffer = '';
                        $state = self::PATH_START_STATE;

                        if ($aStateOverride) {
                            // Terminate this algorithm
                            break 2;
                        }
                    } else {
                        if ($c === '[') {
                            $flag_array = true;
                        } elseif ($c === ']') {
                            $flag_array = false;
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
                        $aStateOverride
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
                                    $url->mScheme
                                ];
                            }

                            if ($isSpecial && $defaultPort === $port) {
                                $url->mPort = null;
                            } else {
                                $url->mPort = $port;
                            }

                            $buffer = '';
                        }

                        if ($aStateOverride) {
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
                    $url->mScheme = 'file';

                    if ($c === ''/* EOF */) {
                        if ($base && $base->mScheme === 'file') {
                            $url->mHost = $base->mHost;
                            $url->mPath = clone $base->mPath;
                            $url->mQuery = $base->mQuery;
                        }
                    } elseif ($c === '/' || $c === '\\') {
                        if ($c === '\\') {
                            // Syntax violation
                        }

                        $state = self::FILE_SLASH_STATE;
                    } elseif ($c === '?') {
                        if ($base && $base->mScheme === 'file') {
                            $url->mHost = $base->mHost;
                            $url->mPath = clone $base->mPath;
                            $url->mQuery = '';
                            $state = self::QUERY_STATE;
                        }
                    } elseif ($c === '#') {
                        if ($base && $base->mScheme === 'file') {
                            $url->mHost = $base->mHost;
                            $url->mPath = clone $base->mPath;
                            $url->mQuery = $base->mQuery;
                            $url->mFragment = '';
                            $state = self::FRAGMENT_STATE;
                        }
                    } else {
                        // Platform-independent Windows drive letter quirk
                        $shouldPopPath = $base &&
                            $base->mScheme === 'file' &&
                            // If c and the first code point of remaining are
                            // not a Windows drive letter
                            (!preg_match(
                                URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                                mb_substr($input, $pointer, 2, $encoding)
                            ) ||
                            // If remaining consists of at least 1 code point
                            mb_strlen(
                                mb_substr(
                                    $input,
                                    $pointer + 1,
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
                                    $pointer + 2,
                                    1,
                                    $encoding
                                )
                            )
                        );

                        if ($shouldPopPath) {
                            $url->mHost = $base->mHost;
                            $url->mPath = clone $base->mPath;
                            self::popURLPath($url);
                        } elseif ($base && $base->mScheme === 'file') {
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
                            $base->mScheme === 'file' &&
                            preg_match(
                                URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER,
                                $base->mPath[0]
                            )
                        ) {
                            // This is a (platform-independent) Windows drive
                            // letter quirk. Both url’s and base’s host are null
                            // under these conditions and therefore not copied.
                            $url->mPath->push($base->mPath[0]);
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
                                $url->mHost = $host;
                            }

                            $buffer = '';
                            $state = self::PATH_START_STATE;
                        }
                    } else {
                        $buffer .= $c;
                    }

                    break;

                case self::PATH_START_STATE:
                    $urlIsSpecial = $url->isSpecial();

                    if ($urlIsSpecial && $c === '\\') {
                        // Syntax violation
                    }

                    $state = self::PATH_STATE;

                    if ($c !== '/' && !($urlIsSpecial && $c === '\\')) {
                        $pointer--;
                    }

                    break;

                case self::PATH_STATE:
                    if ($c === ''/* EOF */ ||
                        $c === '/' ||
                        ($url->isSpecial() && $c === '\\') ||
                        (!$aStateOverride && ($c === '?' || $c === '#'))
                    ) {
                        $urlIsSpecial = $url->isSpecial();

                        if ($urlIsSpecial && $c === '\\') {
                            // Syntax violation
                        }

                        if (isset(self::$doubleDotPathSegment[$buffer])) {
                            self::popURLPath($url);

                            if ($c !== '/' && !($urlIsSpecial && $c === '\\')) {
                                $url->mPath->push('');
                            }
                        } elseif (isset(self::$singleDotPathSegment[$buffer]) &&
                            $c !== '/' &&
                            !($url->isSpecial() && $c === '\\')
                        ) {
                            $url->mPath->push('');
                        } elseif (!isset(
                            self::$singleDotPathSegment[$buffer]
                        )) {
                            if ($url->mScheme === 'file' &&
                                $url->mPath->isEmpty() &&
                                preg_match(
                                    URLUtils::REGEX_WINDOWS_DRIVE_LETTER,
                                    $buffer
                                )
                            ) {
                                if ($url->mHost !== null) {
                                    // Syntax violation
                                }

                                $url->mHost = null;
                                // This is a (platform-independent) Windows
                                // drive letter quirk.
                                $buffer = mb_substr($buffer, 0, 1, $encoding) .
                                    ':' .
                                    mb_substr($buffer, 2, null, $encoding);
                            }

                            $url->mPath->push($buffer);
                        }

                        $buffer = '';

                        if ($c === '?') {
                            $url->mQuery = '';
                            $state = self::QUERY_STATE;
                        } elseif ($c === '#') {
                            $url->mFragment = '';
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
                            $pointer + 1,
                            2,
                            $encoding
                        );

                        if ($c === '%' && !ctype_xdigit($remaining)) {
                            // Syntax violation
                        }

                        if ($c === '%' && strtolower($remaining) === '2e') {
                            $buffer .= '.';
                            $pointer += 2;
                        } else {
                            $buffer .= URLUtils::utf8PercentEncode(
                                $c,
                                URLUtils::ENCODE_SET_DEFAULT
                            );
                        }
                    }

                    break;

                case self::CANNOT_BE_A_BASE_URL_PATH_STATE:
                    if ($c === '?') {
                        $url->mQuery = '';
                        $state = self::QUERY_STATE;
                    } elseif ($c === '#') {
                        $url->mFragment = '';
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
                                mb_substr($input, $pointer + 1, 2, $encoding)
                            )
                        ) {
                            // Syntax violation
                        }

                        if ($c !== ''/* EOF */) {
                            if (!$url->mPath->isEmpty()) {
                                $url->mPath[0] .= URLUtils::utf8PercentEncode(
                                    $c
                                );
                            }
                        }
                    }

                    break;

                case self::QUERY_STATE:
                    if ($c === ''/* EOF */ ||
                        (!$aStateOverride && $c === '#')
                    ) {
                        $oldEncoding = $encoding;

                        if (!$url->isSpecial() ||
                            $url->mScheme === 'ws' ||
                            $url->mScheme === 'wss'
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
                                $url->mQuery .= rawurlencode($buffer[$i]);
                            } else {
                                $url->mQuery .= $buffer[$i];
                            }
                        }

                        $buffer = '';

                        if ($c === '#') {
                            $url->mFragment = '';
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
                                mb_substr($input, $pointer + 1, 2, $encoding)
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
                                mb_substr($input, $pointer + 1, 2, $encoding)
                            )
                        ) {
                            // Syntax violation
                        }

                        $url->mFragment .= URLUtils::utf8PercentEncode($c);
                    }

                    break;
            }
        }

        return $url;
    }

    public function getFragment()
    {
        return $this->mFragment;
    }

    public function getHost()
    {
        return $this->mHost;
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
        switch ($this->mScheme) {
            case 'blob':
                $url = self::basicURLParser($this->mPath[0]);

                if ($url === false) {
                    // Return a new opaque origin
                    return new Origin(
                        $this->mScheme,
                        $this->mHost,
                        $this->mPort,
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
                    $this->mScheme,
                    $this->mHost,
                    $this->mPort,
                    null
                );

            case 'file':
                // Unfortunate as it is, this is left as an exercise to the
                // reader. When in doubt, return a new opaque origin.
                return new Origin(
                    $this->mScheme,
                    $this->mHost,
                    $this->mPort,
                    null,
                    true
                );

            default:
                // Return a new opaque origin.
                return new Origin(
                    $this->mScheme,
                    $this->mHost,
                    $this->mPort,
                    null,
                    true
                );
        }
    }

    public function getPassword()
    {
        return $this->mPassword;
    }

    public function getPath()
    {
        return $this->mPath;
    }

    public function getPort()
    {
        return $this->mPort;
    }

    public function getQuery()
    {
        return $this->mQuery;
    }

    public function getScheme()
    {
        return $this->mScheme;
    }

    public function getUsername()
    {
        return $this->mUsername;
    }

    /**
     * Determines whether two URLs are equal to eachother.
     *
     * @see https://url.spec.whatwg.org/#concept-url-equals
     *
     * @param URLRecord $aOtherUrl A URL to compare equality against.
     *
     * @param bool|null $aExcludeFragment Optional argument that determines
     *     whether a URL's fragment should be factored into equality.
     *
     * @return bool
     */
    public function isEqual(URLRecord $aOtherUrl, $aExcludeFragment = null)
    {
        return $this->serializeURL($aExcludeFragment) ===
            $aOtherUrl->serializeURL($aExcludeFragment);
    }

    public function isFlagSet($aFlag)
    {
        return (bool) ($this->mFlags & $aFlag);
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
        return isset(URLUtils::$specialSchemes[$this->mScheme]);
    }

    /**
     * Serializes a URL object.
     *
     * @see https://url.spec.whatwg.org/#concept-url-serializer
     *
     * @param bool|null $aExcludeFragment Optional argument, that, when
     *     specified will exclude the URL's fragment from being serialized.
     *
     * @return string
     */
    public function serializeURL($aExcludeFragment = null)
    {
        $output = $this->mScheme . ':';

        if ($this->mHost !== null) {
            $output .= '//';

            if ($this->mUsername !== '' || $this->mPassword !== null) {
                $output .= $this->mUsername;

                if ($this->mPassword !== null) {
                    $output .= ':' . $this->mPassword;
                }

                $output .= '@';
            }

            $output .= HostFactory::serialize($this->mHost);

            if ($this->mPort !== null) {
                $output .= ':' . $this->mPort;
            }
        } elseif ($this->mHost === null && $this->mScheme === 'file') {
            $output .= '//';
        }

        if ($this->mFlags & URLRecord::FLAG_CANNOT_BE_A_BASE_URL) {
            $output .= $this->mPath[0];
        } else {
            $output .= '/';

            foreach ($this->mPath as $key => $path) {
                if ($key > 0) {
                    $output .= '/';
                }

                $output .= $path;
            }
        }

        if ($this->mQuery !== null) {
            $output .= '?' . $this->mQuery;
        }

        if (!$aExcludeFragment && $this->mFragment !== null) {
            $output .= '#' . $this->mFragment;
        }

        return $output;
    }

    public function setFragment($aFragment)
    {
        $this->mFragment = $aFragment;
    }

    public function setHost($aHost)
    {
        $this->mHost = $aHost;
    }

    public function setPassword($aPassword)
    {
        $this->mPassword = $aPassword;
    }

    /**
     * Set the URL's password and reparses the URL.
     *
     * @see https://url.spec.whatwg.org/#set-the-password
     *
     * @param string $aPassword The URL's password.
     */
    public function setPasswordSteps($aPassword)
    {
        if ($aPassword === '') {
            $this->mPassword = null;
            return;
        }

        $this->mPassword = '';

        for ($i = 0, $len = mb_strlen($aPassword); $i < $len; $i++) {
            $this->mPassword .= URLUtils::utf8PercentEncode(
                mb_substr($aPassword, $i, 1),
                URLUtils::ENCODE_SET_USERINFO
            );
        }
    }

    public function setPath(\SplDoublyLinkedList $aPath)
    {
        $this->mPath = $aPath;
    }

    public function setPort($aPort)
    {
        $this->mPort = $aPort;
    }

    public function setQuery($aQuery)
    {
        $this->mQuery = $aQuery;
    }

    public function setScheme($aScheme)
    {
        $this->mScheme = $aScheme;
    }

    public function setUsername($aUsername)
    {
        $this->mUsername = $aUsername;
    }

    /**
     * Sets the URLs username and reparses the URL.
     *
     * @see https://url.spec.whatwg.org/#set-the-username
     *
     * @param string $aUsername The URL's username.
     */
    public function setUsernameSteps($aUsername)
    {
        $this->mUsername = '';

        for ($i = 0, $len = mb_strlen($aUsername); $i < $len; $i++) {
            $this->mUsername .= URLUtils::utf8PercentEncode(
                mb_substr($aUsername, $i, 1),
                URLUtils::ENCODE_SET_USERINFO
            );
        }
    }

    /**
     * Parses a URL.
     *
     * @see https://url.spec.whatwg.org/#concept-url-parser
     *
     * @param string $aInput The URL string to be parsed.
     *
     * @param URLRecord|null $aBase A base URL to resolve relative URLs
     *     against.
     *
     * @param string $aEncodingOverride The character encoding of the URL.
     *
     * @return URLRecord|bool
     */
    public static function URLParser(
        $aInput,
        URLRecord $aBase = null,
        $aEncodingOverride = null
    ) {
        $url = self::basicURLParser($aInput, $aBase, $aEncodingOverride);

        if ($url === false) {
            return false;
        }

        if ($url->mScheme != 'blob') {
            return $url;
        }

        // TODO: If the first string in url’s path is not in the blob URL store,
        // return url

        // TODO: Set url’s object to a structured clone of the entry in the blob
        // URL store corresponding to the first string in url’s path

        return $url;
    }

    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @see https://url.spec.whatwg.org/#pop-a-urls-path
     *
     * @param  URLRecord $aUrl The URL of the path that is to be popped.
     */
    protected static function popURLPath(URLRecord $aUrl)
    {
        if (!$aUrl->mPath->isEmpty()) {
            $containsDriveLetter = false;

            foreach ($aUrl->mPath as $path) {
                if (preg_match(
                    URLUtils::REGEX_NORMALIZED_WINDOWS_DRIVE_LETTER,
                    $path
                )) {
                    $containsDriveLetter = true;
                    break;
                }
            }

            if ($aUrl->mScheme !== 'file' || !$containsDriveLetter) {
                $aUrl->mPath->pop();
            }
        }
    }
}
