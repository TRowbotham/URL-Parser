<?php

declare(strict_types=1);

namespace Rowbot\URL\State;

use Rowbot\URL\String\StringBuilderInterface;
use Rowbot\URL\String\StringIteratorInterface;
use Rowbot\URL\String\USVStringInterface;
use Rowbot\URL\ParserConfigInterface;
use Rowbot\URL\URLRecord;

interface State
{
    public const RETURN_OK       = 0;
    public const RETURN_CONTINUE = 1;
    public const RETURN_BREAK    = 2;
    public const RETURN_FAILURE  = 3;

    public function handle(
        ParserConfigInterface $parser,
        USVStringInterface $input,
        StringIteratorInterface $iter,
        StringBuilderInterface $buffer,
        string $codePoint,
        URLRecord $url,
        ?URLRecord $base
    ): int;
}
