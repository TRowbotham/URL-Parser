<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\ParserContext;

interface State
{
    public function handle(ParserContext $context, string $codePoint): StatusCode;
}
