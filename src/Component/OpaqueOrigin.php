<?php

declare(strict_types=1);

namespace Rowbot\URL\Component;

use Rowbot\URL\Origin;

/**
 * @see https://html.spec.whatwg.org/multipage/browsers.html#concept-origin-opaque
 */
final class OpaqueOrigin implements Origin
{
    public function getEffectiveDomain(): ?string
    {
        return null;
    }

    public function isOpaque(): bool
    {
        return true;
    }

    public function isSameOrigin(Origin $other): bool
    {
        return $this === $other;
    }

    public function isSameOriginDomain(Origin $other): bool
    {
        return $this === $other;
    }

    /**
     * @see https://html.spec.whatwg.org/multipage/origin.html#ascii-serialisation-of-an-origin
     */
    public function __toString(): string
    {
        return 'null';
    }
}
