<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Application\ActionResult;
use Src\Application\VendingMachineSession;
use Src\Domain\Money\CoinCollection;
use Src\Domain\VendingMachine\ServiceSnapshot;
use Src\Infrastructure\Cli\Exceptions\InteractiveServiceRequired;
use Src\Infrastructure\Cli\Exceptions\UnrecognizedAction;

final class CliApplication
{
    public function __construct(
        private VendingMachineSession $session,
        private ActionParser $parser,
        private OutputFormatter $formatter,
        private ServicePrompter $prompter,
        private UsageGuide $usageGuide,
    ) {}

    public static function create(): self
    {
        $formatter = new OutputFormatter();
        $standardCatalog = StandardCatalog::machine();

        return new self(
            new VendingMachineSession($standardCatalog),
            new ActionParser(),
            $formatter,
            new ServicePrompter($formatter, $standardCatalog->productSlots()),
            new UsageGuide($formatter),
        );
    }

    public function process(string $input): string
    {
        $lines = [];

        foreach ($this->tokens($input) as $token) {
            $action = $this->parser->parse($token);

            if ($action instanceof ServiceAction) {
                throw new InteractiveServiceRequired();
            }

            $formatted = $this->formatter->format($this->dispatch($action));

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
        fwrite($output, $this->usageGuide->render());

        while (($line = fgets($input)) !== false) {
            foreach ($this->tokens($line) as $token) {
                $formatted = $this->formatter->format($this->execute($this->parser->parse($token), $input, $output));

                if ($formatted !== '') {
                    fwrite($output, $formatted . PHP_EOL);
                }
            }
        }
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    private function execute(ParsedAction $action, $input, $output): ActionResult
    {
        if ($action instanceof ServiceAction) {
            return $this->session->service($this->serviceSnapshot($input, $output));
        }

        return $this->dispatch($action);
    }

    /**
     * @param resource $input
     * @param resource $output
     */
    private function serviceSnapshot($input, $output): ServiceSnapshot
    {
        if (!$this->session->machine()->insertedCoins()->isEmpty()) {
            return ServiceSnapshot::create(CoinCollection::empty());
        }

        return $this->prompter->collect($input, $output);
    }

    private function dispatch(ParsedAction $action): ActionResult
    {
        return match (true) {
            $action instanceof InsertCoinAction => $this->session->insertCoin($action->coin()),
            $action instanceof ReturnCoinsAction => $this->session->returnCoins(),
            $action instanceof SelectProductAction => $this->session->selectProduct($action->selector()),
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
