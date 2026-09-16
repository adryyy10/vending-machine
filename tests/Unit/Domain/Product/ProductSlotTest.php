<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Money;
use Src\Domain\Product\Exceptions\InvalidProductSlotQuantity;
use Src\Domain\Product\Exceptions\ProductSlotOutOfStock;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;

final class ProductSlotTest extends TestCase
{
    public function testCreatesProductSlotSuccessfully(): void
    {
        $product = $this->water();
        $slot = ProductSlot::fromProductAndQuantity($product, 1);

        $this->assertSame($product, $slot->product());
        $this->assertSame(1, $slot->quantity());
    }

    public function testAllowsZeroQuantity(): void
    {
        $slot = ProductSlot::fromProductAndQuantity($this->water(), 0);

        $this->assertSame(0, $slot->quantity());
        $this->assertFalse($slot->hasStock());
    }

    public function testInvalidatesNegativeQuantity(): void
    {
        $this->expectException(InvalidProductSlotQuantity::class);
        $this->expectExceptionMessageIs('Product slot quantity cannot be negative.');

        ProductSlot::fromProductAndQuantity($this->water(), -1);
    }

    public function testDecrementsExactlyOneUnitImmutably(): void
    {
        $product = $this->water();
        $slot = ProductSlot::fromProductAndQuantity($product, 5);

        $decremented = $slot->decrement();

        $this->assertSame(4, $decremented->quantity());
        $this->assertSame($product, $decremented->product());
        $this->assertSame(5, $slot->quantity());
        $this->assertSame($product, $slot->product());
    }

    public function testDecrementingLastUnitLeavesSlotEmpty(): void
    {
        $slot = ProductSlot::fromProductAndQuantity($this->water(), 1);

        $empty = $slot->decrement();

        $this->assertSame(0, $empty->quantity());
        $this->assertFalse($empty->hasStock());
        $this->assertTrue($slot->hasStock());
    }

    public function testCannotDecrementWhenOutOfStock(): void
    {
        $this->expectException(ProductSlotOutOfStock::class);
        $this->expectExceptionMessageIs('Product slot is out of stock.');

        ProductSlot::fromProductAndQuantity($this->water(), 0)->decrement();
    }

    public function testReplacesQuantitySuccessfully(): void
    {
        $product = $this->water();
        $slot = ProductSlot::fromProductAndQuantity($product, 1);

        $replaced = $slot->replaceWithQuantity(15);

        $this->assertSame(1, $slot->quantity());
        $this->assertSame($product, $slot->product());
        $this->assertSame(15, $replaced->quantity());
        $this->assertSame($product, $replaced->product());
    }

    public function testReplaceCanLowerQuantity(): void
    {
        $slot = ProductSlot::fromProductAndQuantity($this->water(), 15);

        $replaced = $slot->replaceWithQuantity(3);

        $this->assertSame(3, $replaced->quantity());
    }

    public function testReplaceCanEmptyTheSlot(): void
    {
        $slot = ProductSlot::fromProductAndQuantity($this->water(), 5);

        $replaced = $slot->replaceWithQuantity(0);

        $this->assertSame(0, $replaced->quantity());
        $this->assertFalse($replaced->hasStock());
        $this->assertTrue($slot->hasStock());
    }

    public function testInvalidReplacingQuantity(): void
    {
        $this->expectException(InvalidProductSlotQuantity::class);
        $this->expectExceptionMessageIs('Product slot quantity cannot be negative.');

        ProductSlot::fromProductAndQuantity($this->water(), 5)->replaceWithQuantity(-15);
    }

    private function water(): Product
    {
        return new Product(
            ProductCode::fromValue('WATER'),
            ProductSelector::fromValue('GET-WATER'),
            Money::fromMinor(65),
        );
    }
}
