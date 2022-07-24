<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Countable;
use Stringable;

interface PathInterface extends Countable, Stringable
{
    public function first(): PathSegment;

    public function isEmpty(): bool;

    public function isOpaque(): bool;

    public function push(PathSegment $path): void;

    /**
     * @see https://url.spec.whatwg.org/#shorten-a-urls-path
     */
    public function shorten(Scheme $scheme): void;
}
