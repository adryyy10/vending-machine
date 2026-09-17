<?php

declare(strict_types=1);

namespace Src\Application;

enum ActionStatus
{
    case COIN_ACCEPTED;
    case PRODUCT_VENDED;
    case COINS_RETURNED;
    case SERVICED;
    case REJECTED;
}
