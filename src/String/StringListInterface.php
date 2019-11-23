<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Countable;
use Iterator;

/**
 * @extends \Iterator<int, \Rowbot\URL\String\USVStringInterface>
 */
interface StringListInterface extends Countable, Iterator
{
    /**
     * @return \Rowbot\URL\String\USVStringInterface
     */
    public function first();

    public function isEmpty(): bool;

    /**
     * @return \Rowbot\URL\String\USVStringInterface
     */
    public function last();

    /**
     * @return \Rowbot\URL\String\USVStringInterface|null
     */
    public function pop();

    /**
     * @param \Rowbot\URL\String\USVStringInterface $item
     */
    public function push($item): void;
}
