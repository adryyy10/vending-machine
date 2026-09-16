<?php

declare(strict_types=1);

namespace Src\Domain\VendingMachine;

enum ServiceResult
{
    case SERVICED;
    case ACTIVE_CUSTOMER_SESSION;
    case CATALOG_MISMATCH;
}
