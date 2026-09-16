<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Money;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Exceptions\InsufficientCoins;
use Src\Domain\Money\Exceptions\InvalidCoinQuantity;
use Src\Domain\Money\Money;

final class CoinCollectionTest extends TestCase
{
    public function testEmptyCollectionHasNoCoinsAndZeroTotal(): void
    {
        $collection = CoinCollection::empty();

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertTrue($collection->total()->equals(Money::fromMinor(0)));
    }

    public function testAddDefaultQuantityIsOne(): void
    {
        $collection = CoinCollection::empty()->add(CoinDenomination::TEN_CENTS);

        $this->assertFalse($collection->isEmpty());
        $this->assertSame(1, $collection->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertTrue($collection->total()->equals(Money::fromMinor(10)));
    }

    public function testAddAccumulatesTheSameDenomination(): void
    {
        $collection = CoinCollection::empty()
            ->add(CoinDenomination::TWENTY_FIVE_CENTS)
            ->add(CoinDenomination::TWENTY_FIVE_CENTS, 2);

        $this->assertSame(3, $collection->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertTrue($collection->total()->equals(Money::fromMinor(75)));
    }

    public function testAddDoesNotMutateTheOriginalCollection(): void
    {
        $original = CoinCollection::empty()->add(CoinDenomination::FIVE_CENTS);

        $original->add(CoinDenomination::FIVE_CENTS);

        $this->assertSame(1, $original->quantityOf(CoinDenomination::FIVE_CENTS));
    }

    #[DataProvider('nonPositiveQuantityProvider')]
    public function testAddRejectsNonPositiveQuantity(int $quantity): void
    {
        $this->expectException(InvalidCoinQuantity::class);
        $this->expectExceptionMessageIs('Coin quantity must be a positive integer.');

        CoinCollection::empty()->add(CoinDenomination::FIVE_CENTS, $quantity);
    }

    public function testQuantityOfMissingDenominationIsZero(): void
    {
        $collection = CoinCollection::empty()->add(CoinDenomination::ONE_HUNDRED_CENTS);

        $this->assertSame(0, $collection->quantityOf(CoinDenomination::TEN_CENTS));
    }

    public function testMergeCombinesQuantitiesByDenomination(): void
    {
        $availableChange = CoinCollection::empty()
            ->add(CoinDenomination::TWENTY_FIVE_CENTS, 2)
            ->add(CoinDenomination::TEN_CENTS);

        $insertedCoins = CoinCollection::empty()
            ->add(CoinDenomination::TWENTY_FIVE_CENTS)
            ->add(CoinDenomination::FIVE_CENTS, 3);

        $merged = $availableChange->merge($insertedCoins);

        $this->assertSame(3, $merged->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(1, $merged->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(3, $merged->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertTrue($merged->total()->equals(Money::fromMinor(100)));
    }

    public function testMergeDoesNotMutateOperands(): void
    {
        $availableChange = CoinCollection::empty()->add(CoinDenomination::TEN_CENTS, 2);
        $insertedCoins = CoinCollection::empty()->add(CoinDenomination::FIVE_CENTS);

        $merged = $availableChange->merge($insertedCoins);

        $this->assertSame(2, $availableChange->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(0, $availableChange->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(1, $insertedCoins->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(0, $insertedCoins->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(2, $merged->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(1, $merged->quantityOf(CoinDenomination::FIVE_CENTS));
    }

    public function testSubtractReducesQuantities(): void
    {
        $availableChange = CoinCollection::empty()
            ->add(CoinDenomination::TWENTY_FIVE_CENTS, 2)
            ->add(CoinDenomination::TEN_CENTS, 3);

        $changeToReturn = CoinCollection::empty()
            ->add(CoinDenomination::TWENTY_FIVE_CENTS)
            ->add(CoinDenomination::TEN_CENTS);

        $remaining = $availableChange->subtract($changeToReturn);

        $this->assertSame(1, $remaining->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(2, $remaining->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertTrue($remaining->total()->equals(Money::fromMinor(45)));
    }

    public function testSubtractingAllCoinsMakesTheCollectionEmpty(): void
    {
        $insertedCoins = CoinCollection::empty()
            ->add(CoinDenomination::TEN_CENTS)
            ->add(CoinDenomination::TEN_CENTS);

        $returned = $insertedCoins->subtract($insertedCoins);

        $this->assertTrue($returned->isEmpty());
        $this->assertSame(0, $returned->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertTrue($returned->total()->equals(Money::fromMinor(0)));
    }

    public function testSubtractDoesNotMutateOperands(): void
    {
        $availableChange = CoinCollection::empty()->add(CoinDenomination::TWENTY_FIVE_CENTS, 3);
        $changeToReturn = CoinCollection::empty()->add(CoinDenomination::TWENTY_FIVE_CENTS);

        $availableSubstracted = $availableChange->subtract($changeToReturn);

        $this->assertSame(3, $availableChange->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(1, $changeToReturn->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(2, $availableSubstracted->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
    }

    public function testCannotSubtractMoreCoinsThanAvailable(): void
    {
        $this->expectException(InsufficientCoins::class);
        $this->expectExceptionMessageIs('Cannot subtract more coins than than available.');

        CoinCollection::empty()
            ->add(CoinDenomination::FIVE_CENTS)
            ->subtract(CoinCollection::empty()->add(CoinDenomination::FIVE_CENTS, 2));
    }

    public function testCannotSubtractAMissingDenomination(): void
    {
        $this->expectException(InsufficientCoins::class);
        $this->expectExceptionMessageIs('Cannot subtract more coins than than available.');

        CoinCollection::empty()
            ->add(CoinDenomination::TEN_CENTS)
            ->subtract(CoinCollection::empty()->add(CoinDenomination::FIVE_CENTS));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function nonPositiveQuantityProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
        ];
    }
}
