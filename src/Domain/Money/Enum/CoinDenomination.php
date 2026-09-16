<?php

declare(strict_types=1);

namespace Src\Domain\Money\Enum;

use Src\Domain\Money\Money;

enum CoinDenomination: int
{
    case FIVE_CENTS = 5;
    case TEN_CENTS = 10;
    case TWENTY_FIVE_CENTS = 25;
    case ONE_HUNDRED_CENTS = 100;

    public static function smallest(): self
    {
        $smallest = self::cases()[0];

        foreach (self::cases() as $coin) {
            if ($coin->value < $smallest->value) {
                $smallest = $coin;
            }
        }

        return $smallest;
    }

    public function money(): Money
    {
        return Money::fromMinor($this->value);
    }
}
