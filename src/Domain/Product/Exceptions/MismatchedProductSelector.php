<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class MismatchedProductSelector extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product selector must match the product code.');
    }
}
