<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

interface State
{
    public const RETURN_OK       = 0;
    public const RETURN_CONTINUE = 1;
    public const RETURN_BREAK    = 2;
    public const RETURN_FAILURE  = 3;

    public function handle(ParserContext $context, string $codePoint): int;
}
