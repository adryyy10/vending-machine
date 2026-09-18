<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\Money\Money;

final class UsageGuide
{
    public function __construct(private OutputFormatter $formatter) {}

    public function render(): string
    {
        $lines = [
            'Welcome to the vending machine!',
            '',
            'You can:',
            '  Insert coins',
            '    Accepted: ' . $this->acceptedCoins(),
            '  Return coins',
            '    Accepted: RETURN-COIN',
            '  Select a product',
        ];

        foreach (StandardCatalog::productSlots()->all() as $slot) {
            $product = $slot->product();
            $lines[] = sprintf(
                '    %s (%s) - Accepted: %s',
                $product->code()->value(),
                $this->formatPrice($product->price()),
                $product->selector()->value(),
            );
        }

        $lines[] = '  Enter service mode';
        $lines[] = '    Accepted: SERVICE';
        $lines[] = '';
        $lines[] = 'Type one or more actions separated by commas, then press Enter.';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    private function acceptedCoins(): string
    {
        $tokens = [];

        foreach (CoinDenomination::cases() as $coin) {
            $tokens[] = $this->formatter->formatCoin($coin);
        }

        return implode(', ', $tokens);
    }

    private function formatPrice(Money $price): string
    {
        $minor = $price->amountMinor();
        $dollars = intdiv($minor, 100);
        $cents = $minor % 100;

        if ($cents === 0) {
            return (string) $dollars;
        }

        return sprintf('%d.%02d', $dollars, $cents);
    }
}
