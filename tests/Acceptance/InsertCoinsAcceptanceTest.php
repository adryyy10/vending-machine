<?php

declare(strict_types=1);

namespace Tests\Acceptance;

final class InsertCoinsAcceptanceTest extends AcceptanceTestCase
{
    public function testInsertingCoinsAlonePrintsNoMachineResult(): void
    {
        $this->assertSame('', $this->machineOutput($this->runCli("0.05, 0.10, 0.25, 1\n")));
    }
}
