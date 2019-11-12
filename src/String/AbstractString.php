<?php

declare(strict_types=1);

namespace Rowbot\URL\String;

use Rowbot\URL\String\Exception\UConverterException;
use UConverter;

use function intval;
use function mb_strlen;
use function sprintf;
use function strlen;
use function substr;

use const U_ZERO_ERROR;

abstract class AbstractString
{
    /**
     * @var string
     */
    protected $string;

    final public function __construct(string $string = '')
    {
        $this->string = $string;
    }

    public function endsWith(string $string): bool
    {
        return substr($this->string, -strlen($string)) === $string;
    }

    public function getIterator(): StringIteratorInterface
    {
        return new Utf8StringIterator($this->string);
    }

    public function isEmpty(): bool
    {
        return $this->string === '';
    }

    public function length(): int
    {
        return mb_strlen($this->string, 'utf-8');
    }

    public function startsWith(string $string): bool
    {
        return substr($this->string, 0, strlen($string)) === $string;
    }

    public function toInt(int $base = 10): int
    {
        return intval($this->string, $base);
    }

    protected static function transcode(
        string $string,
        string $toEncoding,
        string $fromEncoding
    ): string {
        $converter = new UConverter($toEncoding, $fromEncoding);

        if ($converter->getErrorCode() !== U_ZERO_ERROR) {
            throw new UConverterException(sprintf(
                'Attempting to create the UConverter object with encodings "%s" and "%s" failed with message "%s".',
                $toEncoding,
                $fromEncoding,
                $converter->getErrorMessage()
            ), $converter->getErrorCode());
        }

        $transcodedString = $converter->convert($string);

        if ($converter->getErrorCode() !== U_ZERO_ERROR) {
            throw new UConverterException(sprintf(
                'Attempting to transcode from "%s" to "%s" failed with message "%s".',
                $fromEncoding,
                $toEncoding,
                $converter->getErrorMessage()
            ), $converter->getErrorCode());
        }

        return $transcodedString;
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
