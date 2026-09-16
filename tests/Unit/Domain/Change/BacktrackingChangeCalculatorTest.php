<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Change;

use PHPUnit\Framework\TestCase;
use Src\Domain\Change\BacktrackingChangeCalculator;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;

final class BacktrackingChangeCalculatorTest extends TestCase
{
    public function testZeroAmountReturnsEmptyChange(): void
    {
        $change = BacktrackingChangeCalculator::create()->calculate(
            Money::fromMinor(0),
            CoinCollection::empty()->add(CoinDenomination::TEN_CENTS, 5),
        );

        $this->assertNotNull($change);
        $this->assertTrue($change->isEmpty());
    }

    public function testMakesWaterChangeAsQuarterAndDime(): void
    {
        $change = BacktrackingChangeCalculator::create()->calculate(
            Money::fromMinor(35),
            $this->hopper(),
        );

        $this->assertNotNull($change);
        $this->assertTrue($change->total()->equals(Money::fromMinor(35)));
        $this->assertSame(1, $change->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(1, $change->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(0, $change->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(0, $change->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
    }

    public function testPrefersLargerDenominations(): void
    {
        $change = BacktrackingChangeCalculator::create()->calculate(
            Money::fromMinor(100),
            CoinCollection::empty()
                ->add(CoinDenomination::ONE_HUNDRED_CENTS)
                ->add(CoinDenomination::TWENTY_FIVE_CENTS, 4),
        );

        $this->assertNotNull($change);
        $this->assertSame(1, $change->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
        $this->assertSame(0, $change->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
    }

    public function testBacktracksWhenGreedyChoiceCannotFinish(): void
    {
        $hopper = CoinCollection::empty()
            ->add(CoinDenomination::TWENTY_FIVE_CENTS)
            ->add(CoinDenomination::TEN_CENTS, 3);

        $change = BacktrackingChangeCalculator::create()->calculate(
            Money::fromMinor(30),
            $hopper,
        );

        $this->assertNotNull($change);
        $this->assertTrue($change->total()->equals(Money::fromMinor(30)));
        $this->assertSame(0, $change->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(3, $change->quantityOf(CoinDenomination::TEN_CENTS));
    }

    public function testDoesNotUseMoreCoinsThanAvailable(): void
    {
        $change = BacktrackingChangeCalculator::create()->calculate(
            Money::fromMinor(20),
            CoinCollection::empty()->add(CoinDenomination::TEN_CENTS),
        );

        $this->assertNull($change);
    }

    public function testReturnsNullWhenAmountCannotBeMade(): void
    {
        $change = BacktrackingChangeCalculator::create()->calculate(
            Money::fromMinor(10),
            CoinCollection::empty()->add(CoinDenomination::TWENTY_FIVE_CENTS),
        );

        $this->assertNull($change);
    }

    private function hopper(): CoinCollection
    {
        return CoinCollection::empty()
            ->add(CoinDenomination::FIVE_CENTS, 25)
            ->add(CoinDenomination::TEN_CENTS, 10)
            ->add(CoinDenomination::TWENTY_FIVE_CENTS, 5)
            ->add(CoinDenomination::ONE_HUNDRED_CENTS, 2);
    }
}
