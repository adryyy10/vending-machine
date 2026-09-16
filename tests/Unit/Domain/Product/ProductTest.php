<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Money\Money;
use Src\Domain\Product\Exceptions\InvalidProductPrice;
use Src\Domain\Product\Exceptions\MismatchedProductSelector;
use Src\Domain\Product\Product;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;

final class ProductTest extends TestCase
{
    #[DataProvider('catalogProductsProvider')]
    public function testCanBeCreated(string $codeValue, string $selectorValue, int $priceMinor): void
    {
        $code = ProductCode::fromValue($codeValue);
        $selector = ProductSelector::fromValue($selectorValue);
        $price = Money::fromMinor($priceMinor);

        $product = new Product($code, $selector, $price);

        $this->assertTrue($product->code()->equals($code));
        $this->assertTrue($product->selector()->equals($selector));
        $this->assertTrue($product->price()->equals($price));
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function catalogProductsProvider(): array
    {
        return [
            'water' => ['WATER', 'GET-WATER', 65],
            'juice' => ['JUICE', 'GET-JUICE', 100],
            'soda' => ['SODA', 'GET-SODA', 150],
        ];
    }

    public function testRejectsZeroPrice(): void
    {
        $this->expectException(InvalidProductPrice::class);
        $this->expectExceptionMessageIs('Product price must be positive.');

        new Product(
            ProductCode::fromValue('SODA'),
            ProductSelector::fromValue('GET-SODA'),
            Money::fromMinor(0),
        );
    }

    public function testRejectsSelectorThatDoesNotMatchCode(): void
    {
        $this->expectException(MismatchedProductSelector::class);
        $this->expectExceptionMessageIs('Product selector must match the product code.');

        new Product(
            ProductCode::fromValue('SODA'),
            ProductSelector::fromValue('GET-WATER'),
            Money::fromMinor(150),
        );
    }
}
