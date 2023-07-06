<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

enum EncodeSet
{
    case C0_CONTROL;
    case FRAGMENT;
    case QUERY;
    case SPECIAL_QUERY;
    case PATH;
    case USERINFO;
    case COMPONENT;
    case X_WWW_URLENCODED;
}
