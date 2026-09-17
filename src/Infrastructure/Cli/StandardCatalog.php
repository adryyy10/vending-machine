<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;
use Src\Domain\Product\ProductSlot;
use Src\Domain\Product\ProductSlotCollection;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Domain\VendingMachine\VendingMachine;

final class StandardCatalog
{
    public static function machine(): VendingMachine
    {
        return VendingMachine::create(self::hopper(), self::slots());
    }

    public static function serviceSnapshot(): ServiceSnapshot
    {
        return ServiceSnapshot::create(self::hopper())
            ->withQuantity(ProductCode::fromValue('WATER'), 5)
            ->withQuantity(ProductCode::fromValue('JUICE'), 5)
            ->withQuantity(ProductCode::fromValue('SODA'), 5);
    }

    private static function hopper(): CoinCollection
    {
        return CoinCollection::empty()
            ->add(CoinDenomination::FIVE_CENTS, 25)
            ->add(CoinDenomination::TEN_CENTS, 10)
            ->add(CoinDenomination::TWENTY_FIVE_CENTS, 5)
            ->add(CoinDenomination::ONE_HUNDRED_CENTS, 2);
    }

    private static function slots(): ProductSlotCollection
    {
        return ProductSlotCollection::fromSlots(
            self::slot('WATER', 65, 5),
            self::slot('JUICE', 100, 5),
            self::slot('SODA', 150, 5),
        );
    }

    private static function slot(string $code, int $priceMinor, int $quantity): ProductSlot
    {
        return ProductSlot::fromProductAndQuantity(
            Product::create(
                ProductCode::fromValue($code),
                ProductSelector::fromValue('GET-' . $code),
                Money::fromMinor($priceMinor),
            ),
            $quantity,
        );
    }
}
