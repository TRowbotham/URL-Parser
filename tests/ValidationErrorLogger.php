<?php

declare(strict_types=1);

namespace Rowbot\URL\Tests;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

final class ValidationErrorLogger implements LoggerInterface
{
    use LoggerTrait;

    private array $messages;

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->messages[] = [$level, $message, $context];
    }
}
