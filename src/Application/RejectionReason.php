<?php

declare(strict_types=1);

namespace Src\Application;

enum RejectionReason
{
    case UNKNOWN_SELECTION;
    case OUT_OF_STOCK;
    case INSUFFICIENT_FUNDS;
    case EXACT_CHANGE_UNAVAILABLE;
    case ACTIVE_CUSTOMER_SESSION;
    case CATALOG_MISMATCH;
}
