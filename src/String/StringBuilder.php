<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\Component\Path;
use Rowbot\URL\Component\Scheme;

use function intval;
use function preg_match;

class StringBuilder extends AbstractStringBuilder implements StringBuilderInterface
{
    public function isWindowsDriveLetter(): bool
    {
        return preg_match('/^[A-Za-z][:|]$/u', $this->string) === 1;
    }

    public function toInt(int $base = 10): int
    {
        return intval($this->string, $base);
    }

    public function toPath(): Path
    {
        return new Path($this->string);
    }

    public function toScheme(): Scheme
    {
        return new Scheme($this->string);
    }
}
