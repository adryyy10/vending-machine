<?php

declare(strict_types=1);

namespace Src\Domain\Change;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;

final readonly class ChangeCalculator
{
    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function calculate(Money $amountToReturn, CoinCollection $availableChange): ?CoinCollection
    {
        if ($amountToReturn->isZero()) {
            return CoinCollection::empty();
        }

        if ($availableChange->total()->lessThan($amountToReturn)) {
            return null;
        }

        $denominations = CoinDenomination::cases();
        usort(
            $denominations,
            static fn(CoinDenomination $left, CoinDenomination $right): int => $right->value <=> $left->value,
        );

        $used = $this->search($amountToReturn->amountMinor(), $denominations, 0, $availableChange);

        if ($used === null) {
            return null;
        }

        $change = CoinCollection::empty();

        foreach ($used as $denomination => $quantity) {
            $change = $change->add(CoinDenomination::from($denomination), $quantity);
        }

        return $change;
    }

    /**
     * @param list<CoinDenomination> $denominations
     * @param array<int, int> $used
     * @return array<int, int>|null
     */
    private function search(
        int $remaining,
        array $denominations,
        int $index,
        CoinCollection $availableChange,
        array $used = [],
    ): ?array {
        if ($remaining === 0) {
            return $used;
        }

        if (!isset($denominations[$index])) {
            return null;
        }

        $coin = $denominations[$index];
        $maxQuantity = min(
            $availableChange->quantityOf($coin),
            intdiv($remaining, $coin->value),
        );

        for ($quantity = $maxQuantity; $quantity >= 0; $quantity--) {
            $nextUsed = $used;

            if ($quantity > 0) {
                $nextUsed[$coin->value] = $quantity;
            }

            $found = $this->search(
                $remaining - ($quantity * $coin->value),
                $denominations,
                $index + 1,
                $availableChange,
                $nextUsed,
            );

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
