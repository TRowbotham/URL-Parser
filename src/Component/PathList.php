<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\String\AbstractStringList;

use function array_pop;
use function array_shift;
use function count;
use function implode;

/**
 * @extends \Rowbot\URL\String\AbstractStringList<\Rowbot\URL\Component\Path>
 */
class PathList extends AbstractStringList implements PathListInterface
{
    public function shift(): ?Path
    {
        return array_shift($this->list);
    }

    public function shorten(Scheme $scheme): void
    {
        $size = count($this->list);

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
