<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Product\ProductSlotCollection;

final readonly class VendingMachine
{
    private function __construct(
        private CoinCollection $availableChange,
        private CoinCollection $insertedCoins,
        private ProductSlotCollection $productSlots,
    ) {
    }

    public static function create(
        CoinCollection $availableChange,
        ProductSlotCollection $productSlots,
    ): self {
        return new self($availableChange, CoinCollection::empty(), $productSlots);
    }

    public function insertedCoins(): CoinCollection
    {
        return $this->insertedCoins;
    }

    public function availableChange(): CoinCollection
    {
        return $this->availableChange;
    }

    public function productSlots(): ProductSlotCollection
    {
        return $this->productSlots;
    }
}
