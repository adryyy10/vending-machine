<?php

declare(strict_types=1);

namespace Src\Domain\Money\Exceptions;

use DomainException;

final class InvalidCoinQuantity extends DomainException
{
    public function __construct()
    {
        parent::__construct('Coin quantity must be a positive integer.');
    }
}
