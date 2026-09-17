<?php

declare(strict_types=1);

namespace Src\Domain\Product\Enum;

enum PurchaseRejectionReason
{
    case UNKNOWN_SELECTION;
    case OUT_OF_STOCK;
    case INSUFFICIENT_FUNDS;
    case EXACT_CHANGE_UNAVAILABLE;
}
