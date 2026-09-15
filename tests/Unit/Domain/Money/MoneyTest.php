<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Money;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Money;
use Src\Domain\Money\Exceptions\NegativeMoneyNotAllowed;

final class MoneyTest extends TestCase
{
    public function testCanBeCreatedFromMinorUnits(): void
    {
        $waterPrice = Money::fromMinor(65);

        $this->assertSame(65, $waterPrice->amountMinor());
    }

    public function testRejectsNegativeAmounts(): void
    {
        $this->expectException(NegativeMoneyNotAllowed::class);
        $this->expectExceptionMessageIs("Money amount cannot be negative.");

        Money::fromMinor(-1);
    }

    public function testZeroIsValid(): void
    {
        $zero = Money::fromMinor(0);

        $this->assertSame(0, $zero->amountMinor());
    }

    public function testAddReturnsTheSumWithoutMutatingOperands(): void
    {
        $sodaPrice = Money::fromMinor(150);
        $waterPrice = Money::fromMinor(65);

        $sum = $sodaPrice->add($waterPrice);

        $this->assertSame($sum->amountMinor(), ($sodaPrice->amountMinor() + $waterPrice->amountMinor()));
        $this->assertTrue($sodaPrice->equals(Money::fromMinor(150)));
        $this->assertTrue($waterPrice->equals(Money::fromMinor(65)));
    }

    public function testSubtractReturnsTheDifferenceWithoutMutatingOperands(): void
    {
        $inserted = Money::fromMinor(100);
        $waterPrice = Money::fromMinor(65);

        $difference = $inserted->subtract($waterPrice);

        $this->assertTrue($difference->equals(Money::fromMinor(35)));
        $this->assertTrue($inserted->equals(Money::fromMinor(100)));
        $this->assertTrue($waterPrice->equals(Money::fromMinor(65)));
    }

    public function testCannotSubtractALargerAmount(): void
    {
        $this->expectException(NegativeMoneyNotAllowed::class);
        $this->expectExceptionMessageIs("Money amount cannot be negative.");

        Money::fromMinor(65)->subtract(Money::fromMinor(100));
    }

    #[DataProvider('comparisonProvider')]
    public function testComparisons(
        int $leftMinor,
        int $rightMinor,
        bool $greaterThan,
        bool $greaterThanOrEqual,
        bool $lessThan,
        bool $lessThanOrEqual,
    ): void {
        $left = Money::fromMinor($leftMinor);
        $right = Money::fromMinor($rightMinor);

        $this->assertSame($greaterThan, $left->greaterThan($right));
        $this->assertSame($greaterThanOrEqual, $left->greaterThanOrEqual($right));
        $this->assertSame($lessThan, $left->lessThan($right));
        $this->assertSame($lessThanOrEqual, $left->lessThanOrEqual($right));
    }

    /**
     * @return array<string, array{int, int, bool, bool, bool, bool}>
     */
    public static function comparisonProvider(): array
    {
        return [
            'inserted vs water' => [100, 65, true, true, false, false],
            'exact soda price' => [150, 150, false, true, false, true],
            'insufficient for juice' => [25, 100, false, false, true, true],
        ];
    }
}
