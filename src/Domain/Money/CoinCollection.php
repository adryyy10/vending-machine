<?php

declare(strict_types=1);

namespace Src\Domain\Money;

use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Exceptions\InsufficientCoins;
use Src\Domain\Money\Exceptions\InvalidCoinQuantity;

final readonly class CoinCollection
{
    /**
     * @param array<int, int> $quantities denomination minor units => quantity
     */
    private function __construct(private array $quantities)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function add(CoinDenomination $coin, int $quantity = 1): self
    {
        if ($quantity < 1) {
            throw new InvalidCoinQuantity();
        }

        $quantities = $this->quantities;
        $quantities[$coin->value] = ($quantities[$coin->value] ?? 0) + $quantity;

        return new self($quantities);
    }

    public function merge(self $other): self
    {
        $result = $this;

        foreach ($other->quantities as $denomination => $quantity) {
            $result = $result->add(CoinDenomination::from($denomination), $quantity);
        }

        return $result;
    }

    public function subtract(self $other): self
    {
        $quantities = $this->quantities;

        foreach ($other->quantities as $denomination => $quantity) {
            $available = $quantities[$denomination] ?? 0;

            if ($available < $quantity) {
                throw new InsufficientCoins();
            }

            $remaining = $available - $quantity;

            if ($remaining === 0) {
                unset($quantities[$denomination]);
            } else {
                $quantities[$denomination] = $remaining;
            }
        }

        return new self($quantities);
    }

    public function quantityOf(CoinDenomination $coin): int
    {
        return $this->quantities[$coin->value] ?? 0;
    }

    public function total(): Money
    {
        $amountMinor = 0;

        foreach ($this->quantities as $denomination => $quantity) {
            $amountMinor += CoinDenomination::from($denomination)->value * $quantity;
        }

        return Money::fromMinor($amountMinor);
    }

    public function isEmpty(): bool
    {
        return $this->quantities === [];
    }
}
