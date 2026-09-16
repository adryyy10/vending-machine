<?php

declare(strict_types=1);

namespace Src\Domain\Product;

use Src\Domain\Money\Money;
use Src\Domain\Product\Exceptions\InvalidProductPrice;
use Src\Domain\Product\Exceptions\MismatchedProductSelector;

final readonly class Product
{
    public function __construct(
        private ProductCode $code,
        private ProductSelector $selector,
        private Money $price,
    ) {
        if ($this->price->amountMinor() <= 0) {
            throw new InvalidProductPrice();
        }

        if (!$this->selector->matches($this->code)) {
            throw new MismatchedProductSelector();
        }
    }

    public function code(): ProductCode
    {
        return $this->code;
    }

    public function selector(): ProductSelector
    {
        return $this->selector;
    }

    public function price(): Money
    {
        return $this->price;
    }
}
