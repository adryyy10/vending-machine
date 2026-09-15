<?php

declare(strict_types=1);

namespace Src\Domain\Money\Exceptions;

use DomainException;

final class NegativeMoneyNotAllowed extends DomainException
{
    public function __construct()
    {
        parent::__construct('Money amount cannot be negative.');
    }
}
