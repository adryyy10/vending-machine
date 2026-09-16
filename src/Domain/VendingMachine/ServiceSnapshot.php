<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Product\Exceptions\DuplicateProductSlot;
use Src\Domain\Product\Exceptions\InvalidProductSlotQuantity;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSlotCollection;

final readonly class ServiceSnapshot
{
    /**
     * @param array<string, int> $quantities keyed by product code
     */
    private function __construct(
        private CoinCollection $availableChange,
        private array $quantities,
    ) {}

    public static function create(CoinCollection $availableChange): self
    {
        return new self($availableChange, []);
    }

    public function withQuantity(ProductCode $code, int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidProductSlotQuantity();
        }

        $key = $code->value();

        if (isset($this->quantities[$key])) {
            throw new DuplicateProductSlot();
        }

        $quantities = $this->quantities;
        $quantities[$key] = $quantity;

        return new self($this->availableChange, $quantities);
    }

    public function availableChange(): CoinCollection
    {
        return $this->availableChange;
    }

    public function matchesCatalog(ProductSlotCollection $slots): bool
    {
        foreach (array_keys($this->quantities) as $code) {
            if (!$slots->contains(ProductCode::fromValue($code))) {
                return false;
            }
        }

        return true;
    }

    public function restock(ProductSlotCollection $slots): ProductSlotCollection
    {
        foreach ($this->quantities as $code => $quantity) {
            $slots = $slots->replaceQuantity(ProductCode::fromValue($code), $quantity);
        }

        return $slots;
    }
}
