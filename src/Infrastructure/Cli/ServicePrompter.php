<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Domain\Money\CoinCollection;
use Src\Domain\Money\Enum\CoinDenomination;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Infrastructure\Cli\Exceptions\IncompleteServiceInput;

final class ServicePrompter
{
    public function __construct(private OutputFormatter $formatter) {}

    /**
     * @param resource $input
     * @param resource $output
     */
    public function collect($input, $output): ServiceSnapshot
    {
        $hopper = CoinCollection::empty();

        foreach (CoinDenomination::cases() as $coin) {
            $quantity = $this->ask(
                $input,
                $output,
                sprintf('How many coins of %s we will have?', $this->formatter->formatCoin($coin)),
            );

            if ($quantity > 0) {
                $hopper = $hopper->add($coin, $quantity);
            }
        }

        $snapshot = ServiceSnapshot::create($hopper);

        foreach (StandardCatalog::productSlots()->all() as $slot) {
            $snapshot = $snapshot->withQuantity(
                $slot->product()->code(),
                $this->ask(
                    $input,
                    $output,
                    sprintf('How many %s products we will have?', $slot->product()->code()->value()),
                ),
            );
        }

        fwrite($output, 'Everything restocked!' . PHP_EOL);

        return $snapshot;
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    private function ask($input, $output, string $question): int
    {
        while (true) {
            fwrite($output, $question . PHP_EOL);

            $line = fgets($input);

            if ($line === false) {
                throw new IncompleteServiceInput();
            }

            $trimmed = trim($line);

            if (preg_match('/^\d+$/', $trimmed) === 1) {
                return (int) $trimmed;
            }

            fwrite($output, 'Please enter a non-negative whole number.' . PHP_EOL);
        }
    }
}
