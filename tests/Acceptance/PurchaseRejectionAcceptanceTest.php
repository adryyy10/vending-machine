<?php

declare(strict_types=1);

namespace Tests\Acceptance;

final class PurchaseRejectionAcceptanceTest extends AcceptanceTestCase
{
    public function testUnknownSelectionKeepsInsertedCoins(): void
    {
        $output = $this->machineOutput($this->runCli("1, GET-TEA\nRETURN-COIN\n"));

        $this->assertSame("UNKNOWN SELECTION\n1\n", $output);
    }

    public function testInsufficientFundsKeepsInsertedCoinsUntilCustomerAddsMore(): void
    {
        $output = $this->machineOutput($this->runCli("0.10, GET-WATER\n1, GET-WATER\n"));

        $this->assertSame("INSUFFICIENT FUNDS\nWATER, 0.25, 0.10, 0.10\n", $output);
    }

    public function testOutOfStockKeepsInsertedCoins(): void
    {
        $output = $this->machineOutput($this->runCli($this->serviceScript(water: 0) . "1, GET-WATER\nRETURN-COIN\n"));

        $this->assertStringEndsWith("OUT OF STOCK\n1\n", $output);
    }

    public function testExactChangeUnavailableKeepsInsertedCoins(): void
    {
        $output = $this->machineOutput($this->runCli(
            $this->serviceScript(nickels: 0, dimes: 0, quarters: 0, dollars: 2)
            . "1, GET-WATER\nRETURN-COIN\n",
        ));

        $this->assertStringEndsWith("EXACT CHANGE UNAVAILABLE\n1\n", $output);
    }

    private function serviceScript(
        int $nickels = 25,
        int $dimes = 10,
        int $quarters = 5,
        int $dollars = 2,
        int $water = 5,
        int $juice = 5,
        int $soda = 5,
    ): string {
        return implode("\n", [
            'SERVICE',
            $nickels,
            $dimes,
            $quarters,
            $dollars,
            $water,
            $juice,
            $soda,
            '',
        ]);
    }
}
