<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Money;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;

final class CoinDenominationTest extends TestCase
{
    #[DataProvider('denominationProvider')]
    public function testMonetaryContract(CoinDenomination $denomination, int $expectedMinor): void
    {
        $this->assertSame($expectedMinor, $denomination->value);
    }

    #[DataProvider('denominationProvider')]
    public function testMoneyReturnsCorrespondingMoney(CoinDenomination $denomination, int $expectedMinor): void
    {
        $this->assertTrue($denomination->money()->equals(Money::fromMinor($expectedMinor)));
    }

    public function testSmallestDenominationIsFiveCents(): void
    {
        $this->assertSame(CoinDenomination::FIVE_CENTS, CoinDenomination::smallest());
    }

    /**
     * @return array<string, array{CoinDenomination, int}>
     */
    public static function denominationProvider(): array
    {
        return [
            'five cents' => [CoinDenomination::FIVE_CENTS, 5],
            'ten cents' => [CoinDenomination::TEN_CENTS, 10],
            'twenty-five cents' => [CoinDenomination::TWENTY_FIVE_CENTS, 25],
            'one hundred cents' => [CoinDenomination::ONE_HUNDRED_CENTS, 100],
        ];
    }
}
