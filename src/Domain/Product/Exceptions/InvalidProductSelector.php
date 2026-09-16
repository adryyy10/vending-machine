<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class InvalidProductSelector extends DomainException
{
    public function __construct()
    {
        parent::__construct('Product selector must use the GET-{CODE} format.');
    }
}
