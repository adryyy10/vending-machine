<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class InvalidProductPrice extends DomainException
{
    public static function notPositive(): self
    {
        return new self('Product price must be positive.');
    }

    public static function notDivisibleBySmallestCoin(): self
    {
        return new self('Product price must be divisible by the smallest coin denomination.');
    }

    private function __construct(string $message)
    {
        parent::__construct($message);
    }
}
