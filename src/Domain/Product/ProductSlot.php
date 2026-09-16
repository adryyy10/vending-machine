<?php

declare(strict_types=1);

namespace Src\Domain\Product;

use Src\Domain\Product\Exceptions\InvalidProductSlotQuantity;
use Src\Domain\Product\Exceptions\ProductSlotOutOfStock;

final readonly class ProductSlot
{
    private function __construct(
        private Product $product,
        private int $quantity,
    ) {
        if ($quantity < 0) {
            throw new InvalidProductSlotQuantity();
        }
    }

    public static function fromProductAndQuantity(Product $product, int $quantity): self
    {
        return new self($product, $quantity);
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function decrement(): self
    {
        if (!$this->hasStock()) {
            throw new ProductSlotOutOfStock();
        }

        return new self($this->product, $this->quantity - 1);
    }

    public function replaceWithQuantity(int $quantity): self
    {
        return new self($this->product, $quantity);
    }

    public function hasStock(): bool
    {
        return $this->quantity() > 0;
    }
}
