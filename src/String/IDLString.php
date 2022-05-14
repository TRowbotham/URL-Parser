<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\RegexException;

use function mb_convert_encoding;
use function mb_substitute_character;
use function preg_last_error_msg;
use function preg_replace;
use function sprintf;

class IDLString extends AbstractUSVString
{
    public function __construct(string $string = '')
    {
        parent::__construct(self::scrub($string));
    }

    /**
     * See {@link https://github.com/TRowbotham/URL-Parser/issues/7} for why this is necessary.
     */
    public static function scrub(string $input): string
    {
        // Assume you have the following string "Hello\u{D800}World". A lone surrogate separates the words and is
        // not valid UTF-8. When looking at it byte wise, you get the string "Hello\xED\xA0\x80World".
        //
        // Old behavior would treat all 3 bytes of U+D800 as a single unit, replacing it with a single U+FFFD
        // character, resulting in the string "Hello\u{FFFD}World".
        //
        // New behavior treats each byte of U+D800 as its own invalid sequence, replacing each byte with a U+FFFD
        // character, resulting in the stirng "Hello\u{FFFD}\u{FFFD}\u{FFFD}World".
        //
        // We restore the old behavior by first replacing all lone surrogates with a single \u{FFFD}, then letting
        // mb_convert_encoding() handle the remaining invalid by sequences.
        $result = preg_replace('/\xED[\xA0-\xBF][\x80-\xBF]/', "\u{FFFD}", $input);

        if ($result === null) {
            throw new RegexException(sprintf(
                'preg_replace encountered an error with message "%s" trying to clean the input string.',
                preg_last_error_msg()
            ));
        }

        /** @var int|string $sub */
        $sub = mb_substitute_character();
        mb_substitute_character(0xFFFD);

        // Could probably change this to mb_scrub() when PHP >= 7.2.
        $result = mb_convert_encoding($result, 'utf-8', 'utf-8');
        mb_substitute_character($sub);

        return $result;
    }
}
