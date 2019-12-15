<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use IteratorAggregate;

/**
 * @extends \IteratorAggregate<int, string>
 */
interface StringInterface extends IteratorAggregate
{
    public function endsWith(string $string): bool;

    /**
     * @return StringIteratorInterface<int, string>
     */
    public function getIterator(): StringIteratorInterface;

    public function isEmpty(): bool;

    public function length(): int;

    public function startsWith(string $string): bool;

    public function toInt(int $base = 10): int;

    public function __toString(): string;
}
