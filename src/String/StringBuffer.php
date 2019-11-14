<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\Component\Path;
use Rowbot\URL\Component\Scheme;

use function mb_substr;
use function preg_match;

class StringBuffer extends AbstractAppendableString implements StringBufferInterface
{
    public function clear(): void
    {
        $this->string = '';
    }

    public function isWindowsDriveLetter(): bool
    {
        return preg_match('/^[A-Za-z][:|]$/u', $this->string) === 1;
    }

    public function prepend(string $string): void
    {
        $this->string = $string . $this->string;
    }

    public function setCodePointAt(int $index, string $codePoint): void
    {
        $prefix = mb_substr($this->string, 0, $index, 'utf-8');
        $suffix = mb_substr($this->string, $index + 1, null, 'utf-8');
        $this->string = $prefix . $codePoint . $suffix;
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
