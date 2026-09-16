<?php

declare(strict_types=1);

namespace Src\Domain\Product;

use Src\Domain\Product\Exceptions\DuplicateProductSlot;
use Src\Domain\Product\Exceptions\ProductSlotNotFound;

final readonly class ProductSlotCollection
{
    /**
     * @param array<string, ProductSlot> $slots keyed by selector value
     */
    private function __construct(private array $slots) {}

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromSlots(ProductSlot ...$slots): self
    {
        $collection = self::empty();

        foreach ($slots as $slot) {
            $collection = $collection->add($slot);
        }

        return $collection;
    }

    public function add(ProductSlot $slot): self
    {
        foreach ($this->slots as $existing) {
            $existingProduct = $existing->product();
            $incomingProduct = $slot->product();

            if (
                $existingProduct->code()->equals($incomingProduct->code())
                || $existingProduct->selector()->equals($incomingProduct->selector())
            ) {
                throw new DuplicateProductSlot();
            }
        }

        $slots = $this->slots;
        $slots[$slot->product()->selector()->value()] = $slot;

        return new self($slots);
    }

    public function replaceQuantity(ProductCode $code, int $quantity): self
    {
        foreach ($this->slots as $slot) {
            if ($slot->product()->code()->equals($code)) {
                $slots = $this->slots;
                $slots[$slot->product()->selector()->value()] = $slot->replaceWithQuantity($quantity);

                return new self($slots);
            }
        }

        throw new ProductSlotNotFound();
    }

    public function contains(ProductCode $code): bool
    {
        foreach ($this->slots as $slot) {
            if ($slot->product()->code()->equals($code)) {
                return true;
            }
        }

        return false;
    }

    public function slotFor(ProductSelector $selector): ProductSlot
    {
        $slot = $this->slots[$selector->value()] ?? null;

        if (!$slot instanceof ProductSlot) {
            throw new ProductSlotNotFound();
        }

        return $slot;
    }

    /**
     * @return list<ProductSlot>
     */
    public function all(): array
    {
        return array_values($this->slots);
    }

    public function isEmpty(): bool
    {
        return $this->slots === [];
    }
}
