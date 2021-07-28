<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

/**
 * @extends \Rowbot\URL\String\AbstractStringList<\Rowbot\URL\String\USVStringInterface>
 */
class StringList extends AbstractStringList implements StringListInterface
{
    public function current(): USVStringInterface
    {
        return $this->list[$this->cursor];
    }
}
