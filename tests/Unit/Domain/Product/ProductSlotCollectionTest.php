<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Money;
use Src\Domain\Product\Exceptions\DuplicateProductSlot;
use Src\Domain\Product\Exceptions\ProductSlotNotFound;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;
use Src\Domain\Product\ProductSlotCollection;

final class ProductSlotCollectionTest extends TestCase
{
    public function testEmptyCollectionHasNoSlots(): void
    {
        $collection = ProductSlotCollection::empty();

        $this->assertTrue($collection->isEmpty());
        $this->assertSame([], $collection->all());
    }

    public function testFromSlotsKeepsUniqueCatalogSlots(): void
    {
        $water = $this->slot('WATER', 65, 5);
        $juice = $this->slot('JUICE', 100, 5);
        $soda = $this->slot('SODA', 150, 5);

        $collection = ProductSlotCollection::fromSlots($water, $juice, $soda);

        $this->assertFalse($collection->isEmpty());
        $this->assertSame([$water, $juice, $soda], $collection->all());
        $this->assertSame($water, $collection->slotFor(ProductSelector::fromValue('GET-WATER')));
        $this->assertSame($juice, $collection->slotFor(ProductSelector::fromValue('GET-JUICE')));
        $this->assertSame($soda, $collection->slotFor(ProductSelector::fromValue('GET-SODA')));
    }

    public function testAddDoesNotMutateTheOriginalCollection(): void
    {
        $water = $this->slot('WATER', 65, 5);
        $juice = $this->slot('JUICE', 100, 5);

        $original = ProductSlotCollection::fromSlots($water);
        $merged = $original->add($juice);

        $this->assertSame([$water], $original->all());
        $this->assertSame([$water, $juice], $merged->all());
    }

    public function testRejectsDuplicateProductCode(): void
    {
        $this->expectException(DuplicateProductSlot::class);
        $this->expectExceptionMessageIs('A product slot with this code or selector already exists.');

        ProductSlotCollection::fromSlots(
            $this->slot('WATER', 65, 5),
            $this->slot('WATER', 65, 3),
        );
    }

    public function testSlotForUnknownSelectorThrows(): void
    {
        $this->expectException(ProductSlotNotFound::class);
        $this->expectExceptionMessageIs('No product slot matches the given selector.');

        ProductSlotCollection::fromSlots($this->slot('WATER', 65, 5))
            ->slotFor(ProductSelector::fromValue('GET-SODA'));
    }

    private function slot(string $code, int $priceMinor, int $quantity): ProductSlot
    {
        return ProductSlot::fromProductAndQuantity(
            new Product(
                ProductCode::fromValue($code),
                ProductSelector::fromValue('GET-' . $code),
                Money::fromMinor($priceMinor),
            ),
            $quantity,
        );
    }
}
