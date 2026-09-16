<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\VendingMachine;

use PHPUnit\Framework\TestCase;
use Src\Domain\Money\CoinCollection;
use Src\Domain\Product\Exceptions\DuplicateProductSlot;
use Src\Domain\Product\ProductCode;
use Src\Domain\VendingMachine\ServiceSnapshot;

final class ServiceSnapshotTest extends TestCase
{
    public function testRejectsDuplicateStockCounts(): void
    {
        $this->expectException(DuplicateProductSlot::class);
        $this->expectExceptionMessageIs('A product slot with this code or selector already exists.');

        ServiceSnapshot::create(CoinCollection::empty())
            ->withQuantity(ProductCode::fromValue('WATER'), 33)
            ->withQuantity(ProductCode::fromValue('WATER'), 10);
    }
}
