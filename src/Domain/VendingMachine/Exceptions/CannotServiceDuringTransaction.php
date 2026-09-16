<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine\Exceptions;

use DomainException;

final class CannotServiceDuringTransaction extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot service the vending machine while coins are inserted.');
    }
}
