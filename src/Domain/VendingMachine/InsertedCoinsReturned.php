<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

use Src\Domain\Money\CoinCollection;

final readonly class InsertedCoinsReturned
{
    public function __construct(
        private VendingMachine $vendingMachine,
        private CoinCollection $coins,
    ) {
    }

    public function vendingMachine(): VendingMachine
    {
        return $this->vendingMachine;
    }

    public function coins(): CoinCollection
    {
        return $this->coins;
    }
}
