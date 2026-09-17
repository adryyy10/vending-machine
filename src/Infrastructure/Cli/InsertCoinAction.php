<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Domain\Money\Enum\CoinDenomination;

final readonly class InsertCoinAction implements ParsedAction
{
    public function __construct(private CoinDenomination $coin) {}

    public function coin(): CoinDenomination
    {
        return $this->coin;
    }
}
