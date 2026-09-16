<?php

declare(strict_types=1);

namespace Src\Domain\Product\Exceptions;

use DomainException;

final class DuplicateProductSlot extends DomainException
{
    public function __construct()
    {
        parent::__construct('A product slot with this code or selector already exists.');
    }
}
