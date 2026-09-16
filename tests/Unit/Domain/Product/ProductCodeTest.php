<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Product\Exceptions\InvalidProductCode;
use Src\Domain\Product\ProductCode;

final class ProductCodeTest extends TestCase
{
    public function testCanBeCreatedFromValue(): void
    {
        $code = ProductCode::fromValue('SODA');

        $this->assertSame('SODA', $code->value());
    }

    public function testNormalizesWhitespaceAndCase(): void
    {
        $code = ProductCode::fromValue(' water ');

        $this->assertSame('WATER', $code->value());
        $this->assertTrue($code->equals(ProductCode::fromValue('WATER')));
    }

    #[DataProvider('invalidCodeProvider')]
    public function testRejectsInvalidCodes(string $value): void
    {
        $this->expectException(InvalidProductCode::class);
        $this->expectExceptionMessageIs('Product code must be a non-empty alphanumeric identifier.');

        ProductCode::fromValue($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCodeProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'hyphenated' => ['GET-SODA'],
            'symbols' => ['SODA!'],
        ];
    }
}
