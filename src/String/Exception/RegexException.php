<?php

declare(strict_types=1);

namespace Rowbot\URL\String\Exception;

use Rowbot\URL\Exception\URLException;

use function array_flip;
use function array_filter;
use function get_defined_constants;
use function preg_last_error;
use function substr;

use const ARRAY_FILTER_USE_KEY;

class RegexException extends URLException
{
    public static function getNameFromLastCode()
    {
        $code = preg_last_error();
        $constants = get_defined_constants(true)['pcre'];
        $names = array_flip(array_filter($constants, function (string $value): bool {
            return substr($value, -6) === '_ERROR';
        }, ARRAY_FILTER_USE_KEY));

        return $names[$code] ?? 'UNKNOWN_PCRE_ERROR(' . $code . ')';
    }
}
