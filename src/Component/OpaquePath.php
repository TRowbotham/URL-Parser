<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\Exception\URLException;

use function implode;

class OpaquePath extends AbstractPath
{
    public function __construct(PathSegment $path)
    {
        parent::__construct([$path]);
    }

    public function isOpaque(): bool
    {
        return true;
    }

    public function push(PathSegment $path): void
    {
        throw new URLException('Opaque path can only contain a single path');
    }

    /**
     * @see https://url.spec.whatwg.org/#url-path-serializer
     */
    public function __toString(): string
    {
        return implode('', $this->list);
    }
}
