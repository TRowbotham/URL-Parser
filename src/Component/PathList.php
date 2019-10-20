<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\String\AbstractStringList;

use function array_pop;
use function count;
use function implode;

class PathList extends AbstractStringList implements PathListInterface
{
    public function shorten(Scheme $scheme): void
    {
        $size = count($this->list);

        if ($size === 0) {
            return;
        }

        if ($scheme->isFile() && $size === 1 && $this->list[0]->isNormalizedWindowsDriveLetter()) {
            return;
        }

        array_pop($this->list);
    }

    public function __toString(): string
    {
        return implode('/', $this->list);
    }
}
