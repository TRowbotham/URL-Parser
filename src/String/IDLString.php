<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use IntlBreakIterator;
use UConverter;

use function str_replace;
use function substr;

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
        $iter = IntlBreakIterator::createCodePointInstance();
        $iter->setText($input);
        $byteOffset = 0;
        $surrogates = [];

        // IntlCodePointBreakIterator::next() returns the starting byte offset of the last code code point it passed.
        // It only implements \Traversable, so there is no rewind method.
        while (($offset = $iter->next()) !== IntlBreakIterator::DONE) {
            // Assume you have the following string "Hello\u{D800}World". A lone surrogate separates the words and is
            // not valid UTF-8. When looking at it byte wise, you get the string "Hello\xED\xA0\x80World".
            //
            // Old behavior would treat all 3 bytes of U+D800 as a single unit, replacing it with a single U+FFFD
            // character, resulting in the string "Hello\u{FFFD}World".
            //
            // New behavior treats each byte of U+D800 as its own invalid sequence, replacing each byte with a U+FFFD
            // character, resulting in the stirng "Hello\u{FFFD}\u{FFFD}\u{FFFD}World".
            //
            // We restore the old behavior by looking at the difference between the offset of the current byte and the
            // previous byte seen. If the difference is 1, and the returned code point value is 0xFFFD (the
            // replacement character), then we know this is the start of an invalid byte sequence. The old behavior
            // would result in a difference of 3 for surrogates. We must then check the next 2 bytes to see if it forms
            // a surrogate. If it does, we skip over the next 2 bytes, thus treating all three bytes a single unit. We
            // store each unique surrogate that we encounter in an array, then perform a simple string replacement
            // replacing each surrogate found with a single U+FFFD character.
            if ($offset - $byteOffset === 1 && $iter->getLastCodePoint() === 0xFFFD) {
                $bytes = substr($input, $byteOffset, 3);

                if ($bytes >= "\u{D800}" && $bytes <= "\u{DFFF}" && !isset($surrogates[$bytes])) {
                    $surrogates[$bytes] = $bytes;
                    $iter->next(2);
                    $offset += 2;
                }
            }

            $byteOffset = $offset;
        }

        if ($surrogates !== []) {
            $input = str_replace($surrogates, "\u{FFFD}", $input);
        }

        // Handles the replacement of all invalid bytes when using an ICU version < 60.1
        // Handles the replacement of all non-surrogate invalid bytes when using an ICU version >= 60.1.
        return UConverter::transcode($input, 'utf-8', 'utf-8');
    }
}
