<?php

declare(strict_types=1);

namespace Src\Application;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Product\Product;

final readonly class ActionResult
{
    /**
     * @param list<MachineOutput> $outputs
     */
    private function __construct(
        private ActionStatus $status,
        private array $outputs,
        private ?RejectionReason $rejectionReason = null,
    ) {}

    public static function coinAccepted(): self
    {
        return new self(ActionStatus::COIN_ACCEPTED, []);
    }

    public static function productVended(Product $product, CoinCollection $change): self
    {
        return new self(
            ActionStatus::PRODUCT_VENDED,
            [MachineOutput::product($product->code()), ...self::expandCoins($change)],
        );
    }

    public static function coinsReturned(CoinCollection $coins): self
    {
        return new self(ActionStatus::COINS_RETURNED, self::expandCoins($coins));
    }

    public static function serviced(): self
    {
        return new self(ActionStatus::SERVICED, []);
    }

    public static function rejected(RejectionReason $reason): self
    {
        return new self(ActionStatus::REJECTED, [], $reason);
    }

    public function status(): ActionStatus
    {
        return $this->status;
    }

    /**
     * @return list<MachineOutput>
     */
    public function outputs(): array
    {
        return $this->outputs;
    }

    public function rejectionReason(): ?RejectionReason
    {
        return $this->rejectionReason;
    }

    /**
     * @return list<MachineOutput>
     */
    private static function expandCoins(CoinCollection $coins): array
    {
        $denominations = CoinDenomination::cases();
        usort(
            $denominations,
            static fn(CoinDenomination $left, CoinDenomination $right): int => $right->value <=> $left->value,
        );

        $outputs = [];

        foreach ($denominations as $denomination) {
            $quantity = $coins->quantityOf($denomination);

            for ($i = 0; $i < $quantity; $i++) {
                $outputs[] = MachineOutput::coin($denomination);
            }
        }

        return $outputs;
    }
}
