<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Application\ActionResult;
use Src\Application\VendingMachineSession;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Infrastructure\Cli\Exceptions\UnrecognizedAction;

final class CliApplication
{
    public function __construct(
        private VendingMachineSession $session,
        private ActionParser $parser,
        private OutputFormatter $formatter,
        private ServiceSnapshot $serviceSnapshot,
    ) {}

    public static function create(): self
    {
        return new self(
            new VendingMachineSession(StandardCatalog::machine()),
            new ActionParser(),
            new OutputFormatter(),
            StandardCatalog::serviceSnapshot(),
        );
    }

    public function process(string $input): string
    {
        $lines = [];

        foreach ($this->tokens($input) as $token) {
            $formatted = $this->formatter->format($this->dispatch($this->parser->parse($token)));

            if ($formatted !== '') {
                $lines[] = $formatted;
            }
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    public function run($input = STDIN, $output = STDOUT): void
    {
        while (($line = fgets($input)) !== false) {
            $formatted = $this->process($line);

            if ($formatted !== '') {
                fwrite($output, $formatted . PHP_EOL);
            }
        }
    }

    private function dispatch(ParsedAction $action): ActionResult
    {
        return match (true) {
            $action instanceof InsertCoinAction => $this->session->insertCoin($action->coin()),
            $action instanceof ReturnCoinsAction => $this->session->returnCoins(),
            $action instanceof SelectProductAction => $this->session->selectProduct($action->selector()),
            $action instanceof ServiceAction => $this->session->service($this->serviceSnapshot),
            default => throw new UnrecognizedAction('unknown'),
        };
    }

    /**
     * @return list<string>
     */
    private function tokens(string $input): array
    {
        $tokens = preg_split('/\s*,\s*/', trim($input));

        if ($tokens === false) {
            return [];
        }

        return array_values(array_filter($tokens, static fn(string $token): bool => $token !== ''));
    }
}
