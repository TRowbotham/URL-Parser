<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

abstract class AbstractAppendableString extends AbstractString
{
    public function append(string $string): void
    {
        $this->string .= $string;
    }

    public function toUtf8String(): USVStringInterface
    {
        return new Utf8String($this->string);
    }
}
