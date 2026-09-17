<?php

declare(strict_types=1);

namespace Src\Domain\Product;

use Src\Domain\Product\Enum\PurchaseRejectionReason;
use Src\Domain\VendingMachine\VendingMachine;

final readonly class PurchaseRejected
{
    public function __construct(
        private VendingMachine $vendingMachine,
        private PurchaseRejectionReason $reason,
    ) {
    }

    public function vendingMachine(): VendingMachine
    {
        return $this->vendingMachine;
    }

    public function reason(): PurchaseRejectionReason
    {
        return $this->reason;
    }
}
