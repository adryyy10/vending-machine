<?php

declare(strict_types=1);

namespace Src\Application;

use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\PurchaseRejected;
use Src\Domain\Product\Enum\PurchaseRejectionReason;
use Src\Domain\VendingMachine\Enum\ServiceResult;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Domain\VendingMachine\VendingMachine;

final class VendingMachineSession
{
    public function __construct(
        private VendingMachine $machine,
    ) {}

    public function insertCoin(CoinDenomination $coin): ActionResult
    {
        $this->machine = $this->machine->insertCoin($coin);

        return ActionResult::coinAccepted();
    }

    public function returnCoins(): ActionResult
    {
        $returned = $this->machine->returnInsertedCoins();
        $this->machine = $returned->vendingMachine();

        return ActionResult::coinsReturned($returned->coins());
    }

    public function selectProduct(ProductSelector $selector): ActionResult
    {
        $outcome = $this->machine->select($selector);
        $this->machine = $outcome->vendingMachine();

        if ($outcome instanceof PurchaseRejected) {
            return ActionResult::rejected($this->purchaseRejection($outcome->reason()));
        }

        return ActionResult::productVended($outcome->product(), $outcome->change());
    }

    public function service(ServiceSnapshot $snapshot): ActionResult
    {
        $outcome = $this->machine->service($snapshot);
        $this->machine = $outcome->vendingMachine();

        return match ($outcome->result()) {
            ServiceResult::SERVICED => ActionResult::serviced(),
            ServiceResult::ACTIVE_CUSTOMER_SESSION => ActionResult::rejected(RejectionReason::ACTIVE_CUSTOMER_SESSION),
            ServiceResult::CATALOG_MISMATCH => ActionResult::rejected(RejectionReason::CATALOG_MISMATCH),
        };
    }

    public function machine(): VendingMachine
    {
        return $this->machine;
    }

    private function purchaseRejection(PurchaseRejectionReason $reason): RejectionReason
    {
        return match ($reason) {
            PurchaseRejectionReason::UNKNOWN_SELECTION => RejectionReason::UNKNOWN_SELECTION,
            PurchaseRejectionReason::OUT_OF_STOCK => RejectionReason::OUT_OF_STOCK,
            PurchaseRejectionReason::INSUFFICIENT_FUNDS => RejectionReason::INSUFFICIENT_FUNDS,
            PurchaseRejectionReason::EXACT_CHANGE_UNAVAILABLE => RejectionReason::EXACT_CHANGE_UNAVAILABLE,
        };
    }
}
