<?php

declare(strict_types=1);

namespace Src\Domain\Product;

use Src\Domain\Product\Exceptions\InvalidProductSelector;

final readonly class ProductSelector
{
    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (preg_match('/^GET-[A-Z][A-Z0-9]*$/', $normalized) !== 1) {
            throw new InvalidProductSelector();
        }

        $this->value = $normalized;
    }

    public static function fromValue(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function matches(ProductCode $code): bool
    {
        return $this->value === 'GET-' . $code->value();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
