<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\String\AbstractStringBuffer;
use Rowbot\URL\String\CodePoint;

/**
 * Represents a component in a URL's path as an ASCII string.
 */
class Path extends AbstractStringBuffer
{
    /**
     * @see https://url.spec.whatwg.org/#normalized-windows-drive-letter
     */
    public function isNormalizedWindowsDriveLetter(): bool
    {
        return isset($this->string[1])
            && CodePoint::isAsciiAlpha($this->string[0])
            && $this->string[1] === ':';
    }
}
