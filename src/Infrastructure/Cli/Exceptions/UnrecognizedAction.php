<?php

declare(strict_types=1);

namespace Src\Infrastructure\Cli\Exceptions;

use InvalidArgumentException;

final class UnrecognizedAction extends InvalidArgumentException
{
    public function __construct(private string $token)
    {
        parent::__construct(sprintf('Unrecognized action "%s".', $token));
    }

    public function token(): string
    {
        return $this->token;
    }
}
