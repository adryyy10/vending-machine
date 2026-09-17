<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\Exceptions\InvalidProductSelector;
use Src\Domain\Product\ProductSelector;
use Src\Infrastructure\Cli\Exceptions\UnrecognizedAction;

final class ActionParser
{
    public function parse(string $token): ParsedAction
    {
        $normalized = strtoupper(trim($token));

        if ($normalized === 'RETURN-COIN') {
            return new ReturnCoinsAction();
        }

        if ($normalized === 'SERVICE') {
            return new ServiceAction();
        }

        $coin = $this->coin($normalized);

        if ($coin instanceof CoinDenomination) {
            return new InsertCoinAction($coin);
        }

        try {
            return new SelectProductAction(ProductSelector::fromValue($normalized));
        } catch (InvalidProductSelector) {
            throw new UnrecognizedAction($token);
        }
    }

    private function coin(string $token): ?CoinDenomination
    {
        return match ($token) {
            '0.05' => CoinDenomination::FIVE_CENTS,
            '0.10', '0.1' => CoinDenomination::TEN_CENTS,
            '0.25' => CoinDenomination::TWENTY_FIVE_CENTS,
            '1', '1.0', '1.00' => CoinDenomination::ONE_HUNDRED_CENTS,
            default => null,
        };
    }
}
