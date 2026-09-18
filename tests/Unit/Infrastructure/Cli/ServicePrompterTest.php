<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSelector;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Infrastructure\Cli\Exceptions\IncompleteServiceInput;
use Src\Infrastructure\Cli\OutputFormatter;
use Src\Infrastructure\Cli\ServicePrompter;
use Src\Infrastructure\Cli\StandardCatalog;

final class ServicePrompterTest extends TestCase
{
    public function testCollectsHopperAndCatalogQuantities(): void
    {
        $snapshot = $this->prompter()->collect(
            $this->input("3\n7\n7\n3\n5\n8\n2\n"),
            $stdout = $this->stdout(),
        );

        $this->assertSame(3, $snapshot->availableChange()->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(7, $snapshot->availableChange()->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(7, $snapshot->availableChange()->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(3, $snapshot->availableChange()->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
        $this->assertSame(5, $this->quantity($snapshot, 'WATER'));
        $this->assertSame(8, $this->quantity($snapshot, 'JUICE'));
        $this->assertSame(2, $this->quantity($snapshot, 'SODA'));
        $this->assertSame(
            implode(PHP_EOL, [
                'How many coins of 0.05 we will have?',
                'How many coins of 0.10 we will have?',
                'How many coins of 0.25 we will have?',
                'How many coins of 1 we will have?',
                'How many WATER products we will have?',
                'How many JUICE products we will have?',
                'How many SODA products we will have?',
                'Everything restocked!',
                '',
            ]),
            $this->contents($stdout),
        );
    }

    public function testAllowsZeroCoinsAndRePromptsInvalidQuantities(): void
    {
        $snapshot = $this->prompter()->collect(
            $this->input("-1\nabc\n0\n7\n1\n0\n4\n0\n2\n"),
            $stdout = $this->stdout(),
        );

        $this->assertSame(0, $snapshot->availableChange()->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(7, $snapshot->availableChange()->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(1, $snapshot->availableChange()->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(0, $snapshot->availableChange()->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
        $this->assertSame(4, $this->quantity($snapshot, 'WATER'));
        $this->assertSame(0, $this->quantity($snapshot, 'JUICE'));
        $this->assertSame(2, $this->quantity($snapshot, 'SODA'));

        $printed = $this->contents($stdout);

        $this->assertStringContainsString('Please enter a non-negative whole number.', $printed);
        $this->assertSame(3, substr_count($printed, 'How many coins of 0.05 we will have?'));
    }

    public function testRejectsInterruptedServiceInput(): void
    {
        $this->expectException(IncompleteServiceInput::class);

        $this->prompter()->collect($this->input("3\n"), $this->stdout());
    }

    private function prompter(): ServicePrompter
    {
        return new ServicePrompter(new OutputFormatter(), StandardCatalog::productSlots());
    }

    /**
     * @return resource
     */
    private function input(string $contents)
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    /**
     * @return resource
     */
    private function stdout()
    {
        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private function contents($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    private function quantity(ServiceSnapshot $snapshot, string $code): int
    {
        return $snapshot
            ->restock(StandardCatalog::productSlots())
            ->slotFor(ProductSelector::fromValue('GET-' . $code))
            ->quantity();
    }
}
