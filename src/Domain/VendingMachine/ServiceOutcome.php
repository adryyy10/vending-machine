<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

final readonly class ServiceOutcome
{
    public function __construct(
        private VendingMachine $vendingMachine,
        private ServiceResult $result,
    ) {}

    public function vendingMachine(): VendingMachine
    {
        return $this->vendingMachine;
    }

    public function result(): ServiceResult
    {
        return $this->result;
    }
}
