<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\String\StringListInterface;

/**
 * @extends \Rowbot\URL\String\StringListInterface<\Rowbot\URL\Component\Path>
 */
interface PathListInterface extends StringListInterface
{
    /**
     * Removes the last string from a URL's path if its scheme is not "file"
     * and the path does not contain a normalized Windows drive letter.
     *
     * @see https://url.spec.whatwg.org/#shorten-a-urls-path
     */
    public function shorten(Scheme $scheme): void;

    public function __toString(): string;
}
