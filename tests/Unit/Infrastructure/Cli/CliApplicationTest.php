<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Application\VendingMachineSession;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductSelector;
use Src\Infrastructure\Cli\ActionParser;
use Src\Infrastructure\Cli\CliApplication;
use Src\Infrastructure\Cli\Exceptions\InteractiveServiceRequired;
use Src\Infrastructure\Cli\Exceptions\UnrecognizedAction;
use Src\Infrastructure\Cli\OutputFormatter;
use Src\Infrastructure\Cli\ServicePrompter;
use Src\Infrastructure\Cli\StandardCatalog;
use Src\Infrastructure\Cli\UsageGuide;

final class CliApplicationTest extends TestCase
{
    #[DataProvider('exampleProvider')]
    public function testProcessesTheSpecificationExamples(string $input, string $expected): void
    {
        $this->assertSame($expected, CliApplication::create()->process($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function exampleProvider(): array
    {
        return [
            'buy soda with exact change' => ['1, 0.25, 0.25, GET-SODA', 'SODA'],
            'return inserted coins' => ['0.10, 0.10, RETURN-COIN', '0.10, 0.10'],
            'buy water with change' => ['1, GET-WATER', 'WATER, 0.25, 0.10'],
        ];
    }

    public function testServiceInteractivelyRestocksChangeAndProducts(): void
    {
        $session = new VendingMachineSession(StandardCatalog::machine());
        $formatter = new OutputFormatter();
        $application = new CliApplication($session, new ActionParser(), $formatter, new ServicePrompter($formatter), new UsageGuide($formatter));

        $application->run(
            $this->input("SERVICE\n3\n7\n7\n3\n5\n8\n2\n"),
            $stdout = $this->stdout(),
        );

        $machine = $session->machine();

        $this->assertSame(3, $machine->availableChange()->quantityOf(CoinDenomination::FIVE_CENTS));
        $this->assertSame(7, $machine->availableChange()->quantityOf(CoinDenomination::TEN_CENTS));
        $this->assertSame(7, $machine->availableChange()->quantityOf(CoinDenomination::TWENTY_FIVE_CENTS));
        $this->assertSame(3, $machine->availableChange()->quantityOf(CoinDenomination::ONE_HUNDRED_CENTS));
        $this->assertSame(5, $machine->productSlots()->slotFor(ProductSelector::fromValue('GET-WATER'))->quantity());
        $this->assertSame(8, $machine->productSlots()->slotFor(ProductSelector::fromValue('GET-JUICE'))->quantity());
        $this->assertSame(2, $machine->productSlots()->slotFor(ProductSelector::fromValue('GET-SODA'))->quantity());
        $this->assertStringContainsString('How many coins of 0.05 we will have?', $this->contents($stdout));
        $this->assertStringContainsString('How many SODA products we will have?', $this->contents($stdout));
    }

    public function testServiceIsRejectedWhileCoinsAreInsertedWithoutPrompting(): void
    {
        $application = CliApplication::create();

        $application->run(
            $this->input("0.10\nSERVICE\n"),
            $stdout = $this->stdout(),
        );

        $this->assertStringContainsString('Welcome to the vending machine!', $this->contents($stdout));
        $this->assertStringContainsString('ACTIVE CUSTOMER SESSION', $this->contents($stdout));
    }

    public function testProcessRejectsNonInteractiveService(): void
    {
        $this->expectException(InteractiveServiceRequired::class);

        CliApplication::create()->process('SERVICE');
    }

    public function testRunPrintsFormattedLinesFromInput(): void
    {
        $application = CliApplication::create();

        $application->run(
            $this->input("1, GET-WATER\n0.10, RETURN-COIN\n"),
            $stdout = $this->stdout(),
        );

        $printed = $this->contents($stdout);

        $this->assertStringContainsString('Welcome to the vending machine!', $printed);
        $this->assertStringContainsString('Accepted: 0.05, 0.10, 0.25, 1', $printed);
        $this->assertStringContainsString('Accepted: RETURN-COIN', $printed);
        $this->assertStringContainsString('WATER (0.65) - Accepted: GET-WATER', $printed);
        $this->assertStringContainsString('Accepted: SERVICE', $printed);
        $this->assertStringContainsString("WATER, 0.25, 0.10\n0.10\n", $printed);
    }

    public function testProcessRejectsUnrecognizedActions(): void
    {
        $this->expectException(UnrecognizedAction::class);

        CliApplication::create()->process('PUSH');
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
}
