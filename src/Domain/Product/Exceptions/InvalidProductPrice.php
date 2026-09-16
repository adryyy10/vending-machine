<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class InvalidProductPrice extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product price must be positive.');
    }
}
