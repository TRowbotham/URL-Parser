<?php

declare(strict_types=1);

namespace Rowbot\URL;

final class APIParserResult
{
    public readonly ?URLRecord $url;

    public readonly APIParserErrorType $error;

    public function __construct(?URLRecord $urlRecord, APIParserErrorType $error)
    {
        $this->url = $urlRecord;
        $this->error = $error;
    }
}
