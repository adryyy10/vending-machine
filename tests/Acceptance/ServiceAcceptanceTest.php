<?php

declare(strict_types=1);

namespace Tests\Acceptance;

final class ServiceAcceptanceTest extends AcceptanceTestCase
{
    public function testTechnicianRestocksCoinsAndProducts(): void
    {
        $output = $this->machineOutput($this->runCli(implode("\n", [
            'SERVICE',
            '25',
            '10',
            '5',
            '2',
            '0', // Water will be out of stock
            '5',
            '5',
            '1, GET-WATER',
            'RETURN-COIN',
            'SERVICE',
            '25',
            '10',
            '5',
            '2',
            '5',
            '5',
            '5',
            '1, GET-WATER',
            '',
        ])));

        $this->assertStringContainsString("OUT OF STOCK\n1\n", $output);
        $this->assertStringContainsString('Everything restocked!', $output);
        $this->assertStringEndsWith("WATER, 0.25, 0.10\n", $output);
    }

    public function testTechnicianCannotServiceWhileCustomerHasInsertedCoins(): void
    {
        $output = $this->machineOutput($this->runCli("0.10\nSERVICE\nRETURN-COIN\n"));

        $this->assertSame("ACTIVE CUSTOMER SESSION\n0.10\n", $output);
    }
}
