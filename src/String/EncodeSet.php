<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

final class EncodeSet
{
    public const C0_CONTROL       = 1;
    public const FRAGMENT         = 2;
    public const QUERY            = 3;
    public const SPECIAL_QUERY    = 4;
    public const PATH             = 5;
    public const USERINFO         = 6;
    public const COMPONENT        = 7;
    public const X_WWW_URLENCODED = 8;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
