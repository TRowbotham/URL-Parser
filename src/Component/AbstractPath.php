<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\String\Exception\UndefinedIndexException;

use function array_pop;
use function assert;
use function count;

abstract class AbstractPath implements PathInterface
{
    /**
     * @var list<\Rowbot\URL\Component\PathSegment>
     */
    protected array $list;

    /**
     * @param list<\Rowbot\URL\Component\PathSegment> $paths
     */
    public function __construct(array $paths = [])
    {
        $this->list = $paths;
    }

    abstract public function isOpaque(): bool;

    public function count(): int
    {
        return count($this->list);
    }

    public function first(): PathSegment
    {
        return $this->list[0] ?? throw new UndefinedIndexException();
    }

    public function isEmpty(): bool
    {
        return $this->list === [];
    }

    public function shorten(Scheme $scheme): void
    {
        // 1. Assert: url does not have an opaque path.
        assert(!$this->isOpaque());

        // 3. If url’s scheme is "file", path’s size is 1, and path[0] is a normalized Windows drive letter, then
        // return.
        if ($scheme->isFile() && count($this->list) === 1 && $this->list[0]->isNormalizedWindowsDriveLetter()) {
            return;
        }

        // 4. Remove path’s last item, if any.
        array_pop($this->list);
    }

    public function __clone()
    {
        $list = [];

        foreach ($this->list as $path) {
            $list[] = clone $path;
        }

        $this->list = $list;
    }
}
