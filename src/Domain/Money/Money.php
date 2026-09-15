<?php

declare(strict_types=1);

namespace Src\Domain\Money;

use Src\Domain\Money\Exceptions\NegativeMoneyNotAllowed;

final readonly class Money
{
    private function __construct(private int $amountMinor)
    {
        if ($this->amountMinor < 0) {
            throw new NegativeMoneyNotAllowed();
        }
    }

    public static function fromMinor(int $amountMinor): self
    {
        return new self($amountMinor);
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function isZero(): bool
    {
        return $this->amountMinor === 0;
    }

    public function add(self $other): self
    {
        return new self($this->amountMinor + $other->amountMinor);
    }

    public function subtract(self $other): self
    {
        return new self($this->amountMinor - $other->amountMinor);
    }

    public function equals(self $other): bool
    {
        return $this->amountMinor === $other->amountMinor;
    }

    public function greaterThan(self $other): bool
    {
        return $this->amountMinor > $other->amountMinor;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->amountMinor >= $other->amountMinor;
    }

    public function lessThan(self $other): bool
    {
        return $this->amountMinor < $other->amountMinor;
    }

    public function lessThanOrEqual(self $other): bool
    {
        return $this->amountMinor <= $other->amountMinor;
    }
}
