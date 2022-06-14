<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\RegexException;
use Rowbot\URL\Support\EncodingHelper;

use function bin2hex;
use function hexdec;
use function mb_convert_encoding;
use function mb_substitute_character;
use function ord;
use function preg_last_error_msg;
use function preg_replace;
use function preg_split;
use function random_bytes;
use function rawurlencode;
use function sprintf;
use function str_contains;
use function strlen;

use const PREG_SPLIT_DELIM_CAPTURE;

trait PercentEncodeTrait
{
    /**
     * While letting mbstring do the text encoding works in the majority of cases, it isn't perfect due to differences
     * in the encoders for mbstring and those defined by the WHATWG encoding standard. This is the best we can do
     * without actually implementing custom encoders from the WHATWG standard. Mbstring's lack of granular error
     * handling also makes this more complex than it would otherwise need to be.
     *
     * @see https://url.spec.whatwg.org/#string-percent-encode-after-encoding
     *
     * @param string                          $encoding          Output encoding
     * @param string                          $input             UTF-8 encoded string
     * @param \Rowbot\URL\String\EncodeSet::* $percentEncodeSet
     */
    private function percentEncodeAfterEncoding(
        string $encoding,
        string $input,
        int $percentEncodeSet,
        bool $spaceAsPlus = false
    ): string {
        static $random_bytes = null;

        // 1. Let encoder be the result of getting an encoder from encoding.
        $encoder = EncodingHelper::getOutputEncoding($encoding) ?? 'utf-8';

        // 3. Let output be the empty string.
        $output = '';

        // Generate a random string to be used as a placeholder, only changing it if the given input contains the same
        // sequence of bytes.
        while ($random_bytes === null || str_contains($input, $random_bytes)) {
            $random_bytes = bin2hex(random_bytes(16));
        }

        // Replace any existing numeric entities, that are in the hexadecimal format, so that we can distinguish
        // encoding errors below. These will be reinserted later.
        $replacedEntities = 0;
        $input = preg_replace(
            '/&#x([[:xdigit:]]{2,6});/',
            '__' . $random_bytes . '_${1}__',
            $input,
            -1,
            $replacedEntities
        );

        if ($input === null) {
            throw new RegexException(sprintf(
                'preg_replace encountered an error with message "%s".',
                preg_last_error_msg()
            ));
        }

        // 5.1. Let encodeOutput be an empty I/O queue.
        // 5.2. Set potentialError to the result of running encode or fail with inputQueue, encoder, and encodeOutput.
        $substituteChar = mb_substitute_character();
        mb_substitute_character('entity');
        $encodeOutput = mb_convert_encoding($input, $encoder, 'utf-8');
        mb_substitute_character($substituteChar);

        $chunks = preg_split('/&#x([[:xdigit:]]{2,6});/', $encodeOutput, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($chunks === false) {
            throw new RegexException(sprintf(
                'preg_split encountered an error with message "%s".',
                preg_last_error_msg()
            ));
        }

        // Replace the inserted placeholders of original numeric entities with the original text, so they get percent
        // encoded.
        if ($replacedEntities > 0) {
            $chunks = preg_replace("/__{$random_bytes}_([[:xdigit:]]+)__/", '&#x${1};', $chunks);

            if ($chunks === null) {
                throw new RegexException(sprintf(
                    'preg_replace encountered an error with message "%s".',
                    preg_last_error_msg()
                ));
            }
        }

        foreach ($chunks as $key => $bytes) {
            // 5.4. If potentialError is non-null, then append "%26%23", followed by the shortest sequence of ASCII
            // digits representing potentialError in base ten, followed by "%3B", to output.
            //
            // NOTE: This can happen when encoding is not UTF-8.
            if ($key % 2 === 1) {
                $output .= '%26%23' . hexdec($bytes) . '%3B';

                continue;
            }

            // 5.3. For each byte of encodeOutput converted to a byte sequence:
            for ($i = 0, $length = strlen($bytes); $i < $length; ++$i) {
                // 5.3.1. If spaceAsPlus is true and byte is 0x20 (SP), then append U+002B (+) to output and continue.
                if ($spaceAsPlus && $bytes[$i] === "\x20") {
                    $output .= '+';

                    continue;
                }

                // 5.3.2. Let isomorph be a code point whose value is byte’s value.
                $isomorph = ord($bytes[$i]);

                // 5.3.4. If isomorph is not in percentEncodeSet, then append isomorph to output.
                if (!$this->inEncodeSet($isomorph, $percentEncodeSet)) {
                    $output .= $bytes[$i];

                    continue;
                }

                // 5.3.5. Otherwise, percent-encode byte and append the result to output.
                $output .= rawurlencode($bytes[$i]);
            }
        }

        // 6. Return output.
        return $output;
    }

    /**
     * @see https://url.spec.whatwg.org/#c0-control-percent-encode-set
     * @see https://url.spec.whatwg.org/#fragment-percent-encode-set
     * @see https://url.spec.whatwg.org/#query-percent-encode-set
     * @see https://url.spec.whatwg.org/#special-query-percent-encode-set
     * @see https://url.spec.whatwg.org/#path-percent-encode-set
     * @see https://url.spec.whatwg.org/#userinfo-percent-encode-set
     * @see https://url.spec.whatwg.org/#component-percent-encode-set
     *
     * @param \Rowbot\URL\String\EncodeSet::* $percentEncodeSet
     */
    private function inEncodeSet(int $codePoint, int $percentEncodeSet): bool
    {
        switch ($percentEncodeSet) {
            case EncodeSet::X_WWW_URLENCODED:
                switch ($codePoint) {
                    case 0x21:
                    case 0x27:
                    case 0x28:
                    case 0x29:
                    case 0x7E:
                        return true;
                }

                // no break

            case EncodeSet::COMPONENT:
                switch ($codePoint) {
                    case 0x24:
                    case 0x25:
                    case 0x26:
                    case 0x2B:
                    case 0x2C:
                        return true;
                }

                // no break

            case EncodeSet::USERINFO:
                switch ($codePoint) {
                    case 0x2F:
                    case 0x3A:
                    case 0x3B:
                    case 0x3D:
                    case 0x40:
                    case 0x5B:
                    case 0x5C:
                    case 0x5D:
                    case 0x5E:
                    case 0x7C:
                        return true;
                }

                // no break

            case EncodeSet::PATH:
                switch ($codePoint) {
                    case 0x3F:
                    case 0x60:
                    case 0x7B:
                    case 0x7D:
                        return true;

                    default:
                        $percentEncodeSet = EncodeSet::QUERY;
                }
        }

        switch ($percentEncodeSet) {
            case EncodeSet::SPECIAL_QUERY:
                if ($codePoint === 0x27) {
                    return true;
                }

                // no break

            case EncodeSet::QUERY:
                switch ($codePoint) {
                    case 0x20:
                    case 0x22:
                    case 0x23:
                    case 0x3C:
                    case 0x3E:
                        return true;
                }

                break;

            case EncodeSet::FRAGMENT:
                switch ($codePoint) {
                    case 0x20:
                    case 0x22:
                    case 0x3C:
                    case 0x3E:
                    case 0x60:
                        return true;
                }
        }

        // C0_CONTROL
        return $codePoint <= 0x1F || $codePoint > 0x7E;
    }
}
