<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\VendingMachine\Exceptions\CannotServiceDuringTransaction;

final readonly class VendingMachine
{
    private function __construct(
        private CoinCollection $availableChange,
        private CoinCollection $insertedCoins,
        private ProductSlotCollection $productSlots,
    ) {}

    public static function create(
        CoinCollection $availableChange,
        ProductSlotCollection $productSlots,
    ): self {
        return new self($availableChange, CoinCollection::empty(), $productSlots);
    }

    public function insertCoin(CoinDenomination $coin): self
    {
        return new self(
            $this->availableChange,
            $this->insertedCoins->add($coin),
            $this->productSlots,
        );
    }

    public function returnInsertedCoins(): InsertedCoinsReturned
    {
        return new InsertedCoinsReturned(
            new self($this->availableChange, CoinCollection::empty(), $this->productSlots),
            $this->insertedCoins,
        );
    }

    public function service(ServiceSnapshot $serviceSnapshot): self
    {
        if (!$this->insertedCoins->isEmpty()) {
            throw new CannotServiceDuringTransaction();
        }

        $productSlots = $serviceSnapshot->restock($this->productSlots);

        return new self(
            $serviceSnapshot->availableChange(),
            $this->insertedCoins,
            $productSlots,
        );
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
