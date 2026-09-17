<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Infrastructure\Cli\CliApplication;
use Src\Infrastructure\Cli\Exceptions\UnrecognizedAction;

final class CliApplicationTest extends TestCase
{
    #[DataProvider('exampleProvider')]
    public function testProcessesTheSpecificationExamples(string $input, string $expected): void
    {
        $this->assertSame($expected, CliApplication::create()->process($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function exampleProvider(): array
    {
        return [
            'buy soda with exact change' => ['1, 0.25, 0.25, GET-SODA', 'SODA'],
            'return inserted coins' => ['0.10, 0.10, RETURN-COIN', '0.10, 0.10'],
            'buy water with change' => ['1, GET-WATER', 'WATER, 0.25, 0.10'],
        ];
    }

    public function testServiceRestocksWithoutPrinting(): void
    {
        $application = CliApplication::create();

        $this->assertSame('', $application->process('SERVICE'));
    }

    public function testRunPrintsFormattedLinesFromInput(): void
    {
        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        $this->assertIsResource($input);
        $this->assertIsResource($output);

        fwrite($input, "1, GET-WATER\n0.10, RETURN-COIN\n");
        rewind($input);

        CliApplication::create()->run($input, $output);

        rewind($output);
        $printed = stream_get_contents($output);

        $this->assertSame("WATER, 0.25, 0.10\n0.10\n", $printed);
    }

    public function testProcessRejectsUnrecognizedActions(): void
    {
        $this->expectException(UnrecognizedAction::class);

        CliApplication::create()->process('PUSH');
    }
}
