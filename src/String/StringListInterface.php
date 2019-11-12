<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Countable;
use Iterator;

/**
 * @template T
 * @extends \Iterator<int, T>
 */
interface StringListInterface extends Countable, Iterator
{
    /**
     * @return T
     */
    public function first();

    public function isEmpty(): bool;

    /**
     * @return T
     */
    public function last();

    /**
     * @return T|null
     */
    public function pop();

    /**
     * @param T $item
     */
    public function push($item): void;

    /**
     * @return T
     */
    public function shift();
}
