<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine;

use PHPUnit\Framework\TestCase;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;
use Src\Domain\Product\Exceptions\DuplicateProductSlot;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\VendingMachine\VendingMachine;

final class VendingMachineTest extends TestCase
{
    public function testCreatesVendingMachineWithEmptyInsertedCoinsAndCatalogSlots(): void
    {
        $availableChange = $this->availableChange();
        $water = $this->slot('WATER', 65, 5);
        $juice = $this->slot('JUICE', 100, 5);
        $soda = $this->slot('SODA', 150, 5);

        $vendingMachine = VendingMachine::create(
            $availableChange,
            ProductSlotCollection::fromSlots($water, $juice, $soda),
        );

        $this->assertSame($availableChange, $vendingMachine->availableChange());
        $this->assertTrue($vendingMachine->insertedCoins()->isEmpty());

        $slots = $vendingMachine->productSlots();
        $this->assertSame($water, $slots->slotFor(ProductSelector::fromValue('GET-WATER')));
        $this->assertSame($juice, $slots->slotFor(ProductSelector::fromValue('GET-JUICE')));
        $this->assertSame($soda, $slots->slotFor(ProductSelector::fromValue('GET-SODA')));
    }

    private function availableChange(): CoinCollection
    {
        return CoinCollection::empty()
            ->add(CoinDenomination::FIVE_CENTS, 25)
            ->add(CoinDenomination::TEN_CENTS, 10)
            ->add(CoinDenomination::TWENTY_FIVE_CENTS, 5)
            ->add(CoinDenomination::ONE_HUNDRED_CENTS, 2);
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
