<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

use Src\Domain\Change\BacktrackingChangeCalculator;
use Src\Domain\Change\ChangeCalculator;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\Product\ProductVended;
use Src\Domain\Product\PurchaseRejected;
use Src\Domain\Product\Enum\PurchaseRejectionReason;
use Src\Domain\VendingMachine\Enum\ServiceResult;

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

    public function select(ProductSelector $productSelector): ProductVended|PurchaseRejected
    {
        $slot = $this->productSlots->find($productSelector);

        if (!$slot instanceof ProductSlot) {
            return new PurchaseRejected($this, PurchaseRejectionReason::UNKNOWN_SELECTION);
        }

        if (!$slot->hasStock()) {
            return new PurchaseRejected($this, PurchaseRejectionReason::OUT_OF_STOCK);
        }

        $product = $slot->product();

        if ($this->insertedCoins->total()->lessThan($product->price())) {
            return new PurchaseRejected($this, PurchaseRejectionReason::INSUFFICIENT_FUNDS);
        }

        $changeAmount = $this->insertedCoins->total()->subtract($product->price());
        $hopper = $this->availableChange->merge($this->insertedCoins);
        $change = $this->changeCalculator->calculate($changeAmount, $hopper);

        if (!$change instanceof CoinCollection) {
            return new PurchaseRejected($this, PurchaseRejectionReason::EXACT_CHANGE_UNAVAILABLE);
        }

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
