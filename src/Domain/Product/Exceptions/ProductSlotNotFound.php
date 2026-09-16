<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class ProductSlotNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No product slot matches the given selector.');
    }
}
