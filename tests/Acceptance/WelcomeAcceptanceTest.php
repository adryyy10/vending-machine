<?php

declare(strict_types=1);

namespace Tests\Acceptance;

final class WelcomeAcceptanceTest extends AcceptanceTestCase
{
    public function testSessionShowsAcceptedActionsOnStart(): void
    {
        $output = $this->runCli('');

        $this->assertStringContainsString('Welcome to the vending machine!', $output);
        $this->assertStringContainsString('Accepted: 0.05, 0.10, 0.25, 1', $output);
        $this->assertStringContainsString('Accepted: RETURN-COIN', $output);
        $this->assertStringContainsString('WATER (0.65) - Accepted: GET-WATER', $output);
        $this->assertStringContainsString('JUICE (1) - Accepted: GET-JUICE', $output);
        $this->assertStringContainsString('SODA (1.50) - Accepted: GET-SODA', $output);
        $this->assertStringContainsString('Accepted: SERVICE', $output);
    }
}
