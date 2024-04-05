<?php

declare(strict_types=1);

namespace Rowbot\URL;

final class APIParserResult
{
    public readonly ?URLRecord $url;

    public readonly ParserErrorType $error;

    public function __construct(?URLRecord $urlRecord, ParserErrorType $error)
    {
        $this->url = $urlRecord;
        $this->error = $error;
    }
}
