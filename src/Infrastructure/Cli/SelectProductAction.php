<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli;

use Src\Domain\Product\ProductSelector;

final readonly class SelectProductAction implements ParsedAction
{
    public function __construct(private ProductSelector $selector) {}

    public function selector(): ProductSelector
    {
        return $this->selector;
    }
}
