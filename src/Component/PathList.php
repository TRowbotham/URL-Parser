<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use function implode;

class PathList extends AbstractPath
{
    public function isOpaque(): bool
    {
        return false;
    }

    public function push(PathSegment $path): void
    {
        $this->list[] = $path;
    }

    /**
     * @see https://url.spec.whatwg.org/#url-path-serializer
     */
    public function __toString(): string
    {
        if (!isset($this->list[0])) {
            return '';
        }

        return '/' . implode('/', $this->list);
    }
}
