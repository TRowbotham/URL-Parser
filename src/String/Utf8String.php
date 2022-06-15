<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use function mb_scrub;
use function mb_substitute_character;

class Utf8String extends AbstractUSVString
{
    public static function fromUnsafe(string $input): self
    {
        return new self(self::scrub($input));
    }

    public static function scrub(string $input): string
    {
        $substituteChar = mb_substitute_character();
        mb_substitute_character(0xFFFD);
        $input = mb_scrub($input, 'utf-8');
        mb_substitute_character($substituteChar);

        return $input;
    }
}
