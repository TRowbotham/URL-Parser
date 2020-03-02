<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

interface USVStringInterface extends StringInterface
{
    public function append(string $string): self;

    /**
     * @return array<int, string>
     */
    public function matches(string $pattern): array;

    public function replaceRegex(
        string $pattern,
        string $replacement,
        int $limit = -1,
        int &$count = 0
    ): self;

    /**
     * @return \Rowbot\URL\String\StringListInterface
     */
    public function split(string $delimiter, int $limit = null): StringListInterface;

    public function startsWithTwoAsciiHexDigits(): bool;

    /**
     * @see https://url.spec.whatwg.org/#start-with-a-windows-drive-letter
     */
    public function startsWithWindowsDriveLetter(): bool;

    public function substr(int $start, int $length = null): self;
}
