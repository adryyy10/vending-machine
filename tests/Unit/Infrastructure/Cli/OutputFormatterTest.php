<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\TestCase;
use Src\Application\ActionResult;
use Src\Application\RejectionReason;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Infrastructure\Cli\OutputFormatter;

final class OutputFormatterTest extends TestCase
{
    public function testFormatsAPurchaseWithTheProductFirstThenCoins(): void
    {
        $formatted = (new OutputFormatter())->format(
            ActionResult::productVended(
                $this->product('WATER', 65),
                CoinCollection::empty()
                    ->add(CoinDenomination::TWENTY_FIVE_CENTS)
                    ->add(CoinDenomination::TEN_CENTS),
            ),
        );

        $this->assertSame('WATER, 0.25, 0.10', $formatted);
    }

    public function testFormatsAnExactPurchaseAsTheProductOnly(): void
    {
        $formatted = (new OutputFormatter())->format(
            ActionResult::productVended(
                $this->product('SODA', 150),
                CoinCollection::empty(),
            ),
        );

        $this->assertSame('SODA', $formatted);
    }

    public function testFormatsReturnedCoins(): void
    {
        $formatted = (new OutputFormatter())->format(
            ActionResult::coinsReturned(
                CoinCollection::empty()
                    ->add(CoinDenomination::TEN_CENTS)
                    ->add(CoinDenomination::TEN_CENTS),
            ),
        );

        $this->assertSame('0.10, 0.10', $formatted);
    }

    public function testFormatsAcceptedCoinsAndServiceAsEmpty(): void
    {
        $formatter = new OutputFormatter();

        $this->assertSame('', $formatter->format(ActionResult::coinAccepted()));
        $this->assertSame('', $formatter->format(ActionResult::serviced()));
    }

    public function testFormatsRejections(): void
    {
        $formatted = (new OutputFormatter())->format(
            ActionResult::rejected(RejectionReason::INSUFFICIENT_FUNDS),
        );

        $this->assertSame('INSUFFICIENT FUNDS', $formatted);
    }

    private function product(string $code, int $priceMinor): Product
    {
        return Product::create(
            ProductCode::fromValue($code),
            ProductSelector::fromValue('GET-' . $code),
            Money::fromMinor($priceMinor),
        );
    }
}
