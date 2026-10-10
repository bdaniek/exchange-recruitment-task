<?php

declare(strict_types=1);

namespace App\Enum;

enum Currency: string
{
    case PLN = 'PLN'; // Polish zloty
    case EUR = 'EUR'; // Euro
    case USD = 'USD'; // US Dollar
    case GBP = 'GBP'; // British Pound
    case JPY = 'JPY'; // Yen
    case CHF = 'CHF'; // Swiss Franc
    case HUF = 'HUF'; // Hungarian Forint
}
