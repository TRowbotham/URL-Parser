<?php

declare(strict_types=1);

namespace Rowbot\URL;

enum APIParserErrorType
{
    case NONE;

    case URL;

    case BASE;
}
