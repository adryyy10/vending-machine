<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli\Exceptions;

use RuntimeException;

final class IncompleteServiceInput extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Service was interrupted before all restock quantities were provided.');
    }
}
