<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSelector;
use Src\Infrastructure\Cli\ActionParser;
use Src\Infrastructure\Cli\InsertCoinAction;
use Src\Infrastructure\Cli\ReturnCoinsAction;
use Src\Infrastructure\Cli\SelectProductAction;
use Src\Infrastructure\Cli\ServiceAction;
use Src\Infrastructure\Cli\Exceptions\UnrecognizedAction;

final class ActionParserTest extends TestCase
{
    #[DataProvider('coinTokenProvider')]
    public function testParsesCoinInsertions(string $token, CoinDenomination $coin): void
    {
        $action = (new ActionParser())->parse($token);

        $this->assertInstanceOf(InsertCoinAction::class, $action);
        $this->assertSame($coin, $action->coin());
    }

    /**
     * @return array<string, array{string, CoinDenomination}>
     */
    public static function coinTokenProvider(): array
    {
        return [
            'five cents' => ['0.05', CoinDenomination::FIVE_CENTS],
            'ten cents' => ['0.10', CoinDenomination::TEN_CENTS],
            'twenty five cents' => ['0.25', CoinDenomination::TWENTY_FIVE_CENTS],
            'one hundred cents' => ['1', CoinDenomination::ONE_HUNDRED_CENTS],
            'trimmed one hundred cents' => [' 1 ', CoinDenomination::ONE_HUNDRED_CENTS],
        ];
    }

    #[DataProvider('productTokenProvider')]
    public function testParsesProductSelections(string $token, string $selector): void
    {
        $action = (new ActionParser())->parse($token);

        $this->assertInstanceOf(SelectProductAction::class, $action);
        $this->assertTrue($action->selector()->equals(ProductSelector::fromValue($selector)));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function productTokenProvider(): array
    {
        return [
            'water' => ['GET-WATER', 'GET-WATER'],
            'juice lowercase' => ['get-juice', 'GET-JUICE'],
            'soda with spaces' => [' GET-SODA ', 'GET-SODA'],
        ];
    }

    public function testParsesReturnCoin(): void
    {
        $action = (new ActionParser())->parse('RETURN-COIN');

        $this->assertInstanceOf(ReturnCoinsAction::class, $action);
    }

    public function testParsesService(): void
    {
        $action = (new ActionParser())->parse('service');

        $this->assertInstanceOf(ServiceAction::class, $action);
    }

    public function testRejectsUnrecognizedTokens(): void
    {
        $this->expectException(UnrecognizedAction::class);
        $this->expectExceptionMessageIs('Unrecognized action "STEAL".');

        (new ActionParser())->parse('STEAL');
    }
}
