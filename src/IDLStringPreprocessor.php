<?php

declare(strict_types=1);

namespace Rowbot\URL;

use IntlBreakIterator;
use UConverter;

use function str_replace;
use function substr;

class IDLStringPreprocessor
{
    /**
     * @var \IntlCodePointBreakIterator
     */
    private $iter;

    public function __construct()
    {
        $this->iter = IntlBreakIterator::createCodePointInstance();
    }

    public function process(string $input): string
    {
        $this->iter->setText($input);
        $byteOffset = 0;
        $surrogates = [];

        while ($this->iter->next() !== IntlBreakIterator::DONE) {
            $codePoint = $this->iter->getLastCodePoint();
            $offset = $this->iter->current();

            if ($offset - $byteOffset === 1 && $codePoint === 0xFFFD) {
                $bytes = substr($input, $byteOffset, 3);

                if ($bytes >= "\u{D800}" && $bytes <= "\u{DFFF}" && !isset($surrogates[$bytes])) {
                    $surrogates[$bytes] = $bytes;
                    $this->iter->next(2);
                }
            }

            $byteOffset = $offset;
        }

        if ($surrogates !== []) {
            $input = str_replace($surrogates, "\u{FFFD}", $input);
        }

        return UConverter::transcode($input, 'utf-8', 'utf-8');
    }
}
