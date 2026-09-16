<?php

declare(strict_types=1);

namespace Src\Domain\Product;

use Src\Domain\Money\CoinCollection;
use Src\Domain\VendingMachine\VendingMachine;

final readonly class ProductVended
{
    public function __construct(
        private VendingMachine $vendingMachine,
        private Product $product,
        private CoinCollection $change,
    ) {
    }

    public function vendingMachine(): VendingMachine
    {
        return $this->vendingMachine;
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function change(): CoinCollection
    {
        return $this->change;
    }
}
