<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Application\ActionResult;
use Src\Application\ActionStatus;
use Src\Application\MachineOutput;
use Src\Application\RejectionReason;
use Src\Domain\Money\Enum\CoinDenomination;

final class OutputFormatter
{
    public function format(ActionResult $result): string
    {
        return match ($result->status()) {
            ActionStatus::COIN_ACCEPTED, ActionStatus::SERVICED => '',
            ActionStatus::REJECTED => $this->formatRejection($result->rejectionReason()),
            ActionStatus::PRODUCT_VENDED, ActionStatus::COINS_RETURNED => $this->formatOutputs($result->outputs()),
        };
    }

    /**
     * @param list<MachineOutput> $outputs
     */
    private function formatOutputs(array $outputs): string
    {
        $products = [];
        $coins = [];

        foreach ($outputs as $output) {
            $productCode = $output->productCode();

            if ($productCode !== null) {
                $products[] = $productCode->value();
                continue;
            }

            $denomination = $output->denomination();

            if ($denomination instanceof CoinDenomination) {
                $coins[] = $this->formatCoin($denomination);
            }
        }

        return implode(', ', [...$products, ...$coins]);
    }

    private function formatCoin(CoinDenomination $coin): string
    {
        return match ($coin) {
            CoinDenomination::FIVE_CENTS => '0.05',
            CoinDenomination::TEN_CENTS => '0.10',
            CoinDenomination::TWENTY_FIVE_CENTS => '0.25',
            CoinDenomination::ONE_HUNDRED_CENTS => '1',
        };
    }

    private function formatRejection(?RejectionReason $reason): string
    {
        if (!$reason instanceof RejectionReason) {
            return 'REJECTED';
        }

        return str_replace('_', ' ', $reason->name);
    }
}
