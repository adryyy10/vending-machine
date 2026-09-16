<?php

declare(strict_types=1);

namespace Src\Domain\Change;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Money;

interface ChangeCalculator
{
    public function calculate(
        Money $amountToReturn,
        CoinCollection $availableChange,
    ): ?CoinCollection;
}
