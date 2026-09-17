<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Src\Application\ActionStatus;
use Src\Application\RejectionReason;
use Src\Application\VendingMachineSession;
use Src\Domain\Change\BacktrackingChangeCalculator;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Domain\VendingMachine\VendingMachine;

final class VendingMachineSessionTest extends TestCase
{
    public function testInsertCoinAcceptsTheCoin(): void
    {
        $session = $this->session();

        $result = $session->insertCoin(CoinDenomination::TEN_CENTS);

        $this->assertSame(ActionStatus::COIN_ACCEPTED, $result->status());
        $this->assertSame([], $result->outputs());
        $this->assertSame(1, $session->machine()->insertedCoins()->quantityOf(CoinDenomination::TEN_CENTS));
    }

    public function testReturnCoinsOutputsTheInsertedCoins(): void
    {
        $session = $this->session();
        $session->insertCoin(CoinDenomination::TEN_CENTS);
        $session->insertCoin(CoinDenomination::TEN_CENTS);

        $result = $session->returnCoins();

        $this->assertSame(ActionStatus::COINS_RETURNED, $result->status());
        $this->assertCount(2, $result->outputs());
        $this->assertSame(CoinDenomination::TEN_CENTS, $result->outputs()[0]->denomination());
        $this->assertSame(CoinDenomination::TEN_CENTS, $result->outputs()[1]->denomination());
        $this->assertTrue($session->machine()->insertedCoins()->isEmpty());
    }

    public function testSelectProductVendsSodaWithExactChange(): void
    {
        $session = $this->session();
        $session->insertCoin(CoinDenomination::ONE_HUNDRED_CENTS);
        $session->insertCoin(CoinDenomination::TWENTY_FIVE_CENTS);
        $session->insertCoin(CoinDenomination::TWENTY_FIVE_CENTS);

        $result = $session->selectProduct(ProductSelector::fromValue('GET-SODA'));

        $this->assertSame(ActionStatus::PRODUCT_VENDED, $result->status());
        $this->assertCount(1, $result->outputs());
        $this->assertTrue($result->outputs()[0]->productCode()?->equals(ProductCode::fromValue('SODA')));
        $this->assertTrue($session->machine()->insertedCoins()->isEmpty());
        $this->assertSame(4, $session->machine()->productSlots()->slotFor(ProductSelector::fromValue('GET-SODA'))->quantity());
    }

    public function testSelectProductVendsWaterAndChange(): void
    {
        $session = $this->session();
        $session->insertCoin(CoinDenomination::ONE_HUNDRED_CENTS);

        $result = $session->selectProduct(ProductSelector::fromValue('GET-WATER'));

        $this->assertSame(ActionStatus::PRODUCT_VENDED, $result->status());
        $this->assertCount(3, $result->outputs());
        $this->assertTrue($result->outputs()[0]->productCode()?->equals(ProductCode::fromValue('WATER')));
        $this->assertSame(CoinDenomination::TWENTY_FIVE_CENTS, $result->outputs()[1]->denomination());
        $this->assertSame(CoinDenomination::TEN_CENTS, $result->outputs()[2]->denomination());
    }

    public function testSelectUnknownProductIsRejected(): void
    {
        $session = $this->session();
        $session->insertCoin(CoinDenomination::ONE_HUNDRED_CENTS);

        $result = $session->selectProduct(ProductSelector::fromValue('GET-TEA'));

        $this->assertSame(ActionStatus::REJECTED, $result->status());
        $this->assertSame(RejectionReason::UNKNOWN_SELECTION, $result->rejectionReason());
        $this->assertSame([], $result->outputs());
        $this->assertSame(1, $session->machine()->insertedCoins()->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
    }

    public function testSelectOutOfStockProductIsRejected(): void
    {
        $session = $this->session(waterQuantity: 0);
        $session->insertCoin(CoinDenomination::ONE_HUNDRED_CENTS);

        $result = $session->selectProduct(ProductSelector::fromValue('GET-WATER'));

        $this->assertSame(ActionStatus::REJECTED, $result->status());
        $this->assertSame(RejectionReason::OUT_OF_STOCK, $result->rejectionReason());
    }

    public function testSelectWithInsufficientFundsIsRejected(): void
    {
        $session = $this->session();
        $session->insertCoin(CoinDenomination::TEN_CENTS);

        $result = $session->selectProduct(ProductSelector::fromValue('GET-WATER'));

        $this->assertSame(ActionStatus::REJECTED, $result->status());
        $this->assertSame(RejectionReason::INSUFFICIENT_FUNDS, $result->rejectionReason());
    }

    public function testSelectWhenExactChangeIsUnavailableIsRejected(): void
    {
        $session = $this->session(emptyHopper: true);
        $session->insertCoin(CoinDenomination::ONE_HUNDRED_CENTS);

        $result = $session->selectProduct(ProductSelector::fromValue('GET-WATER'));

        $this->assertSame(ActionStatus::REJECTED, $result->status());
        $this->assertSame(RejectionReason::EXACT_CHANGE_UNAVAILABLE, $result->rejectionReason());
        $this->assertSame(1, $session->machine()->insertedCoins()->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
    }

    public function testServiceRestocksTheMachine(): void
    {
        $session = $this->session();

        $result = $session->service(
            ServiceSnapshot::create($this->availableChange())
                ->withQuantity(ProductCode::fromValue('WATER'), 33),
        );

        $this->assertSame(ActionStatus::SERVICED, $result->status());
        $this->assertSame([], $result->outputs());
        $this->assertSame(33, $session->machine()->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->quantity());
    }

    public function testServiceIsRejectedWhileCoinsAreInserted(): void
    {
        $session = $this->session();
        $session->insertCoin(CoinDenomination::TEN_CENTS);

        $result = $session->service(
            ServiceSnapshot::create($this->availableChange())
                ->withQuantity(ProductCode::fromValue('WATER'), 33),
        );

        $this->assertSame(ActionStatus::REJECTED, $result->status());
        $this->assertSame(RejectionReason::ACTIVE_CUSTOMER_SESSION, $result->rejectionReason());
        $this->assertSame(1, $session->machine()->insertedCoins()->quantityOf(CoinDenomination::TEN_CENTS));
    }

    private function session(int $waterQuantity = 5, bool $emptyHopper = false): VendingMachineSession
    {
        $hopper = $emptyHopper ? CoinCollection::empty() : $this->availableChange();

        return new VendingMachineSession(
            VendingMachine::create(
                $hopper,
                ProductSlotCollection::fromSlots(
                    $this->slot('WATER', 65, $waterQuantity),
                    $this->slot('JUICE', 100, 5),
                    $this->slot('SODA', 150, 5),
                ),
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
