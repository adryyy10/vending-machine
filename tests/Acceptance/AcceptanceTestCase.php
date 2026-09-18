<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use PHPUnit\Framework\TestCase;
use Src\Infrastructure\Cli\CliApplication;
use Src\Infrastructure\Cli\OutputFormatter;
use Src\Infrastructure\Cli\UsageGuide;

abstract class AcceptanceTestCase extends TestCase
{
    protected function runCli(string $stdin): string
    {
        $input = fopen('php://memory', 'r+');
        $output = fopen('php://memory', 'r+');
        $this->assertIsResource($input);
        $this->assertIsResource($output);

        fwrite($input, $stdin);
        rewind($input);

        CliApplication::create()->run($input, $output);

        rewind($output);

        return (string) stream_get_contents($output);
    }

    protected function machineOutput(string $fullOutput): string
    {
        $guide = (new UsageGuide(new OutputFormatter()))->render();

        if ($guide === '') {
            self::fail('Usage guide must not be empty.');
        }

        $this->assertStringStartsWith($guide, $fullOutput);

        return substr($fullOutput, strlen($guide));
    }
}
