<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class ProductSlotNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No matching product slot was found.');
    }
}
