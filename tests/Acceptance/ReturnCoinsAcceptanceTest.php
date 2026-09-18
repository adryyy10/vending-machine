<?php

declare(strict_types=1);

namespace Tests\Acceptance;

final class ReturnCoinsAcceptanceTest extends AcceptanceTestCase
{
    public function testCustomerCancelsAndGetsInsertedCoinsBack(): void
    {
        $output = $this->machineOutput($this->runCli("0.10, 0.10, RETURN-COIN\n"));

        $this->assertSame("0.10, 0.10\n", $output);
    }
}
