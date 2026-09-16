<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

use Src\Domain\Change\BacktrackingChangeCalculator;
use Src\Domain\Change\ChangeCalculator;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\Product\ProductVended;

final readonly class VendingMachine
{
    private function __construct(
        private CoinCollection $availableChange,
        private CoinCollection $insertedCoins,
        private ProductSlotCollection $productSlots,
        private ChangeCalculator $changeCalculator,
    ) {
    }

    public static function create(
        CoinCollection $availableChange,
        ProductSlotCollection $productSlots,
        ?ChangeCalculator $changeCalculator = null,
    ): self {
        return new self(
            $availableChange,
            CoinCollection::empty(),
            $productSlots,
            $changeCalculator ?? BacktrackingChangeCalculator::create(),
        );
    }

    public function insertCoin(CoinDenomination $coin): self
    {
        return new self(
            $this->availableChange,
            $this->insertedCoins->add($coin),
            $this->productSlots,
            $this->changeCalculator,
        );
    }

    public function returnInsertedCoins(): InsertedCoinsReturned
    {
        return new InsertedCoinsReturned(
            new self(
                $this->availableChange,
                CoinCollection::empty(),
                $this->productSlots,
                $this->changeCalculator,
            ),
            $this->insertedCoins,
        );
    }

    public function service(ServiceSnapshot $serviceSnapshot): ServiceOutcome
    {
        if (!$this->insertedCoins->isEmpty()) {
            return new ServiceOutcome($this, ServiceResult::ACTIVE_CUSTOMER_SESSION);
        }

        if (!$serviceSnapshot->matchesCatalog($this->productSlots)) {
            return new ServiceOutcome($this, ServiceResult::CATALOG_MISMATCH);
        }

        $serviced = new self(
            $serviceSnapshot->availableChange(),
            $this->insertedCoins,
            $serviceSnapshot->restock($this->productSlots),
            $this->changeCalculator,
        );

        return new ServiceOutcome($serviced, ServiceResult::SERVICED);
    }

    public function select(ProductSelector $productSelector): ProductVended
    {
        $product = $this->productSlots->slotFor($productSelector)->product();
        $changeDue = $this->insertedCoins->total()->subtract($product->price());
        $hopper = $this->availableChange->merge($this->insertedCoins);
        $change = $this->changeCalculator->calculate($changeDue, $hopper);

        $vendingMachine = new self(
            $hopper->subtract($change),
            CoinCollection::empty(),
            $this->productSlots->decrement($productSelector),
            $this->changeCalculator,
        );

        return new ProductVended($vendingMachine, $product, $change);
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
