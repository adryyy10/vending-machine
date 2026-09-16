<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class ProductSlotOutOfStock extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product slot is out of stock.');
    }
}
