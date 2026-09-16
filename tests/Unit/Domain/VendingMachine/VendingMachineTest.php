<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine;

use PHPUnit\Framework\TestCase;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\VendingMachine\ServiceResult;
use Src\Domain\VendingMachine\ServiceSnapshot;
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

    public function testInsertCoinAddsToInsertedCoinsWithoutTouchingTheHopper(): void
    {
        $machine = $this->machine();

        $updated = $machine->insertCoin(CoinDenomination::TWENTY_FIVE_CENTS);

        $this->assertTrue($machine->insertedCoins()->isEmpty());
        $this->assertSame(1, $updated->insertedCoins()->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame($machine->availableChange(), $updated->availableChange());
    }

    public function testReturnInsertedCoinsGivesBackTheSameCoinsAndClearsTheSlot(): void
    {
        $machine = $this->machine()
            ->insertCoin(CoinDenomination::TEN_CENTS)
            ->insertCoin(CoinDenomination::TEN_CENTS);

        $result = $machine->returnInsertedCoins();

        $this->assertSame(2, $result->coins()->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertTrue($result->coins()->total()->equals(Money::fromMinor(20)));
        $this->assertTrue($result->vendingMachine()->insertedCoins()->isEmpty());
        $this->assertSame($machine->availableChange(), $result->vendingMachine()->availableChange());
        $this->assertSame($machine->productSlots(), $result->vendingMachine()->productSlots());
        $this->assertSame(2, $machine->insertedCoins()->quantityOf(CoinDenomination::TEN_CENTS));
    }

    public function testReturnInsertedCoinsWhenNothingWasInsertedReturnsEmpty(): void
    {
        $machine = $this->machine();

        $result = $machine->returnInsertedCoins();

        $this->assertTrue($result->coins()->isEmpty());
        $this->assertTrue($result->vendingMachine()->insertedCoins()->isEmpty());
    }

    public function testServiceReplacesStockQuantitiesAndChangeWithoutChangingPrices(): void
    {
        $machine = $this->machine();

        $outcome = $machine->service(
            ServiceSnapshot::create(
                CoinCollection::empty()
                    ->add(CoinDenomination::FIVE_CENTS, 1)
                    ->add(CoinDenomination::TEN_CENTS, 1)
                    ->add(CoinDenomination::TWENTY_FIVE_CENTS, 1)
                    ->add(CoinDenomination::ONE_HUNDRED_CENTS, 1),
            )
                ->withQuantity(ProductCode::fromValue('WATER'), 33)
                ->withQuantity(ProductCode::fromValue('JUICE'), 41)
                ->withQuantity(ProductCode::fromValue('SODA'), 12),
        );

        $resultMachine = $outcome->vendingMachine();

        $this->assertSame(ServiceResult::SERVICED, $outcome->result());

        $this->assertSame(5, $machine->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->quantity());
        $this->assertSame(5, $machine->productSlots()->slotFor(ProductSelector::fromValue('GET-JUICE'))->quantity());
        $this->assertSame(5, $machine->productSlots()->slotFor(ProductSelector::fromValue('GET-SODA'))->quantity());
        $this->assertSame(25, $machine->availableChange()->quantityOf(CoinDenomination::FIVE_CENTS));

        $this->assertSame(33, $resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->quantity());
        $this->assertSame(41, $resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-JUICE'))->quantity());
        $this->assertSame(12, $resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-SODA'))->quantity());
        $this->assertTrue($resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->product()->price()->equals(Money::fromMinor(65)));
        $this->assertTrue($resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-JUICE'))->product()->price()->equals(Money::fromMinor(100)));
        $this->assertTrue($resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-SODA'))->product()->price()->equals(Money::fromMinor(150)));
        $this->assertSame(1, $resultMachine->availableChange()->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(1, $resultMachine->availableChange()->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(1, $resultMachine->availableChange()->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(1, $resultMachine->availableChange()->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
    }

    public function testServiceCanRestockASubsetOfProducts(): void
    {
        $outcome = $this->machine()->service(
            ServiceSnapshot::create($this->availableChange())
                ->withQuantity(ProductCode::fromValue('WATER'), 33),
        );

        $resultMachine = $outcome->vendingMachine();

        $this->assertSame(ServiceResult::SERVICED, $outcome->result());

        $this->assertSame(33, $resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->quantity());
        $this->assertSame(5, $resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-JUICE'))->quantity());
        $this->assertSame(5, $resultMachine->productSlots()->slotFor(ProductSelector::fromValue('GET-SODA'))->quantity());
    }

    public function testServiceCannotAddAnUnknownProduct(): void
    {
        $machine = $this->machine();

        $outcome = $machine->service(
            ServiceSnapshot::create($this->availableChange())
                ->withQuantity(ProductCode::fromValue('TEA'), 10),
        );

        $this->assertSame(ServiceResult::CATALOG_MISMATCH, $outcome->result());
        $this->assertSame($machine, $outcome->vendingMachine());
    }

    public function testCannotServiceWhileCoinsAreInserted(): void
    {
        $machine = $this->machine()->insertCoin(CoinDenomination::TEN_CENTS);

        $outcome = $machine->service(
            ServiceSnapshot::create($this->availableChange())
                ->withQuantity(ProductCode::fromValue('WATER'), 33),
        );

        $this->assertSame(ServiceResult::ACTIVE_CUSTOMER_SESSION, $outcome->result());
        $this->assertSame($machine, $outcome->vendingMachine());
        $this->assertSame(1, $outcome->vendingMachine()->insertedCoins()->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(5, $outcome->vendingMachine()->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->quantity());
    }

    private function machine(): VendingMachine
    {
        return VendingMachine::create(
            $this->availableChange(),
            ProductSlotCollection::fromSlots(
                $this->slot('WATER', 65, 5),
                $this->slot('JUICE', 100, 5),
                $this->slot('SODA', 150, 5),
            ),
        );
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
            Product::create(
                ProductCode::fromValue($code),
                ProductSelector::fromValue('GET-' . $code),
                Money::fromMinor($priceMinor),
            ),
            $quantity,
        );
    }
}
