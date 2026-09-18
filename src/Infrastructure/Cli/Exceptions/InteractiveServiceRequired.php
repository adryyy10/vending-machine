<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli\Exceptions;

use RuntimeException;

final class InteractiveServiceRequired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('SERVICE must be run interactively so restock quantities can be entered.');
    }
}
