<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class InvalidProductCode extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product code must be a non-empty alphanumeric identifier.');
    }
}
