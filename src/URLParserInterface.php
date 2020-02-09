<?php

declare(strict_types=1);

namespace Rowbot\URL;

use Rowbot\URL\State\State;
use Rowbot\URL\String\USVStringInterface;

interface URLParserInterface
{
    /**
     * The parser can parse both absolute and relative URLs. If a relative URL is given, a base URL must also be given
     * so that an absolute URL can be resolved. It can also parse individual parts of a URL when the default starting
     * state is overridden, however, a previously parsed URL record object must be provided in this case.
     *
     * @see https://url.spec.whatwg.org/#concept-basic-url-parser
     *
     * @param \Rowbot\URL\String\USVStringInterface $input            A UTF-8 encoded string consisting of only scalar
     *                                                                values, excluding surrogates.
     * @param \Rowbot\URL\URLRecord|null            $base             (optional) This represents the base URL, which in
     *                                                                most cases, is the document's URL, it may also be
     *                                                                a node's base URI or whatever base URL you wish to
     *                                                                resolve relative URLs against. Default is null.
     * @param string|null                           $encodingOverride (optional) Overrides the default ouput encoding,
     *                                                                which is UTF-8. This option exists solely for the
     *                                                                use of the HTML specification and should never be
     *                                                                changed.
     * @param \Rowbot\URL\URLRecord|null            $url              (optional) This represents an existing URL record
     *                                                                object that should be modified based on the input
     *                                                                URL and optional base URL. Default is null.
     * @param \Rowbot\URL\State\State|null          $stateOverride    (optional) An object implementing the
     *                                                                \Rowbot\URL\State interface that overrides the
     *                                                                default start state, which is the Scheme Start
     *                                                                State. Default is null.
     *
     * @return \Rowbot\URL\URLRecord|false Returns a URL object upon successfully parsing the input or false if parsing
     *                                     input failed.
     */
    public function parse(
        USVStringInterface $input,
        URLRecord $base = null,
        string $encodingOverride = null,
        URLRecord $url = null,
        State $stateOverride = null
    );
}
