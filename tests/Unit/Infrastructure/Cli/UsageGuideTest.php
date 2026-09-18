<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\TestCase;
use Src\Infrastructure\Cli\OutputFormatter;
use Src\Infrastructure\Cli\UsageGuide;

final class UsageGuideTest extends TestCase
{
    public function testListsEveryAcceptedActionWithItsWording(): void
    {
        $guide = (new UsageGuide(new OutputFormatter()))->render();

        $this->assertStringContainsString('Welcome to the vending machine!', $guide);
        $this->assertStringContainsString('Insert coins', $guide);
        $this->assertStringContainsString('Accepted: 0.05, 0.10, 0.25, 1', $guide);
        $this->assertStringContainsString('Return coins', $guide);
        $this->assertStringContainsString('Accepted: RETURN-COIN', $guide);
        $this->assertStringContainsString('Select a product', $guide);
        $this->assertStringContainsString('WATER (0.65) - Accepted: GET-WATER', $guide);
        $this->assertStringContainsString('JUICE (1) - Accepted: GET-JUICE', $guide);
        $this->assertStringContainsString('SODA (1.50) - Accepted: GET-SODA', $guide);
        $this->assertStringContainsString('Enter service mode', $guide);
        $this->assertStringContainsString('Accepted: SERVICE', $guide);
    }
}
