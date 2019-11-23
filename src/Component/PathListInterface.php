<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Countable;
use Iterator;

/**
 * @extends \Iterator<int, \Rowbot\URL\Component\Path>
 */
interface PathListInterface extends Countable, Iterator
{
    /**
     * @return \Rowbot\URL\Component\Path
     */
    public function first();

    public function isEmpty(): bool;

    /**
     * @return \Rowbot\URL\Component\Path|null
     */
    public function pop();

    /**
     * @param \Rowbot\URL\Component\Path $path
     */
    public function push($path): void;

    public function shift(): ?Path;

    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @see https://url.spec.whatwg.org/#shorten-a-urls-path
     */
    public function shorten(Scheme $scheme): void;

    public function __toString(): string;
}
