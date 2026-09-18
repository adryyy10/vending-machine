<?php

declare(strict_types=1);

namespace Tests\Acceptance;

final class BuyProductAcceptanceTest extends AcceptanceTestCase
{
    public function testCustomerBuysWaterAndReceivesChange(): void
    {
        $output = $this->machineOutput($this->runCli("1, GET-WATER\n"));

        $this->assertSame("WATER, 0.25, 0.10\n", $output);
    }

    public function testCustomerBuysSodaWithExactChange(): void
    {
        $output = $this->machineOutput($this->runCli("1, 0.25, 0.25, GET-SODA\n"));

        $this->assertSame("SODA\n", $output);
    }

    public function testCustomerBuysJuiceWithADollar(): void
    {
        $output = $this->machineOutput($this->runCli("1, GET-JUICE\n"));

        $this->assertSame("JUICE\n", $output);
    }

    public function testCustomerCanBuySeveralProductsInTheSameSession(): void
    {
        $output = $this->machineOutput($this->runCli("1, GET-JUICE\n1, GET-WATER\n"));

        $this->assertSame("JUICE\nWATER, 0.25, 0.10\n", $output);
    }
}
