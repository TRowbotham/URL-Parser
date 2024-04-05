<?php

declare(strict_types=1);

namespace Rowbot\URL;

enum ParserErrorType
{
    case NONE;

    case URL;

    case BASE;
}
