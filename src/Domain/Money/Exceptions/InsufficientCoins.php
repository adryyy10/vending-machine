<?php

declare(strict_types=1);

namespace Src\Domain\Money\Exceptions;

use DomainException;

final class InsufficientCoins extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot subtract more coins than than available.');
    }
}
