<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\CodePoint;
use Rowbot\URL\String\StringBufferInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

/**
 * @see https://url.spec.whatwg.org/#authority-state
 */
class AuthorityState implements State
{
    /**
     * @var bool
     */
    private $atTokenSeen;

    /**
     * @var bool
     */
    private $passwordTokenSeen;

    public function __construct()
    {
        $this->atTokenSeen = false;
        $this->passwordTokenSeen = false;
    }

    public function handle(
        ParserConfigInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBufferInterface $buffer,
        string $codePoint,
        URLRecord $url,
        ?URLRecord $base
    ): int {
        if ($codePoint === '@') {
            // Validation error.

            if ($this->atTokenSeen) {
                $buffer->prepend('%40');
            }

            $this->atTokenSeen = true;

            foreach ($buffer as $bufferCodePoint) {
                if ($bufferCodePoint === ':' && !$this->passwordTokenSeen) {
                    $this->passwordTokenSeen = true;

                    continue;
                }

                $userInfo = $this->passwordTokenSeen ? 'password' : 'username';
                $url->{$userInfo} .= CodePoint::utf8PercentEncode(
                    $bufferCodePoint,
                    CodePoint::USERINFO_PERCENT_ENCODE_SET
                );
            }

            $buffer->clear();

            return self::RETURN_OK;
        }

        if (
            (
                $codePoint === CodePoint::EOF
                || $codePoint === '/'
                || $codePoint === '?'
                || $codePoint === '#'
            )
            || ($url->scheme->isSpecial() && $codePoint === '\\')
        ) {
            if ($this->atTokenSeen && $buffer->isEmpty()) {
                // Validation error.
                return self::RETURN_FAILURE;
            }

            $iter->seek(-($buffer->length() + 1));
            $buffer->clear();
            $parser->setState(new HostState());

            return self::RETURN_OK;
        }

        $buffer->append($codePoint);

        return self::RETURN_OK;
    }
}
