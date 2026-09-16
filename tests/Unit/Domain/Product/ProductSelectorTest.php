<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Product\Exceptions\InvalidProductSelector;
use Src\Domain\Product\ProductCode;
use Src\Domain\Product\ProductSelector;

final class ProductSelectorTest extends TestCase
{
    public function testCanBeCreatedFromValue(): void
    {
        $selector = ProductSelector::fromValue('GET-SODA');

        $this->assertSame('GET-SODA', $selector->value());
    }

    public function testNormalizesWhitespaceAndCase(): void
    {
        $selector = ProductSelector::fromValue(' get-water ');

        $this->assertSame('GET-WATER', $selector->value());
        $this->assertTrue($selector->equals(ProductSelector::fromValue('GET-WATER')));
    }

    public function testMatchesCorrespondingProductCode(): void
    {
        $selector = ProductSelector::fromValue('GET-SODA');

        $this->assertTrue($selector->matches(ProductCode::fromValue('SODA')));
        $this->assertFalse($selector->matches(ProductCode::fromValue('WATER')));
    }

    #[DataProvider('invalidSelectorProvider')]
    public function testRejectsInvalidSelectors(string $value): void
    {
        $this->expectException(InvalidProductSelector::class);
        $this->expectExceptionMessageIs('Product selector must use the GET-{CODE} format.');

        ProductSelector::fromValue($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSelectorProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'missing prefix' => ['SODA'],
            'space instead of hyphen' => ['GET SODA'],
            'trailing hyphen' => ['GET-'],
        ];
    }
}
