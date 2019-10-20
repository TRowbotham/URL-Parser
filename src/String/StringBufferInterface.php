<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\Component\Path;
use Rowbot\URL\Component\Scheme;

interface StringBufferInterface extends StringInterface
{
    public function append(string $string): void;

    public function clear(): void;

    public function equals(string $string): bool;

    /**
     * @see https://url.spec.whatwg.org/#windows-drive-letter
     */
    public function isWindowsDriveLetter(): bool;

    public function prepend(string $string): void;

    public function setCodePointAt(int $index, string $codePoint): void;

    public function toPath(): Path;

    public function toScheme(): Scheme;

    public function toUtf8String(): USVStringInterface;
}
