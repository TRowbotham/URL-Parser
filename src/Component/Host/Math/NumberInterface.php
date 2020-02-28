<?php

declare(strict_types=1);

namespace Rowbot\URL\Component\Host\Math;

interface NumberInterface
{
    public function add(self $number): self;

    public function intdiv(int $number): self;

    public function isEqualTo(self $number): bool;

    public function isGreaterThan(int $number): bool;

    public function isGreaterThanOrEqualTo(int $number): bool;

    public function mod(int $number): self;

    public function multipliedBy(int $number): self;

    public function toInt(): int;

    public function __toString(): string;
}
