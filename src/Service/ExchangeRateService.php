<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Currency;

class ExchangeRateService
{
    public function getExchangeRate(Currency $currency): float
    {
        return match ($currency) {
            Currency::PLN => 1.0,
            Currency::EUR => 4.2389,
            Currency::USD => 3.6467,
            Currency::GBP => 4.881,
            Currency::JPY => 0.0229,
            Currency::CHF => 4.6347,
            Currency::HUF => 0.0118,
        };
    }

    public function getExchangeRateBetween(Currency $from, Currency $to): float
    {
        return $this->getExchangeRate($from) / $this->getExchangeRate($to);
    }
}
