<?php

declare(strict_types=1);

namespace Src\Application;

use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\ProductCode;

final readonly class MachineOutput
{
    private function __construct(
        private ?ProductCode $productCode,
        private ?CoinDenomination $coin,
    ) {}

    public static function product(ProductCode $productCode): self
    {
        return new self($productCode, null);
    }

    public static function coin(CoinDenomination $coin): self
    {
        return new self(null, $coin);
    }

    public function productCode(): ?ProductCode
    {
        return $this->productCode;
    }

    public function denomination(): ?CoinDenomination
    {
        return $this->coin;
    }
}
