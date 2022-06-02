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

    public function shorten(Scheme $scheme): void;
}
