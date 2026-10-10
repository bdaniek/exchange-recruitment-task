<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Currency;

class SpreadService
{
    private const array LIQUIDITY_SCORE = [
        Currency::USD->value => 1.00,
        Currency::EUR->value => 0.95,
        Currency::GBP->value => 0.85,
        Currency::CHF->value => 0.80,
        Currency::JPY->value => 0.75,
        Currency::PLN->value => 0.55,
        Currency::HUF->value => 0.40,
    ];

    private const float BASE_SPREAD_PERCENT = 0.5;

    public function calculateSpread(
        float $price,
        Currency $fromCurrency,
        Currency $toCurrency,
    ): string {
        if ($fromCurrency === $toCurrency) {
            return number_format(0, 2, '.', '');
        }

        $fromLiquidity = self::LIQUIDITY_SCORE[$fromCurrency->value];
        $toLiquidity = self::LIQUIDITY_SCORE[$toCurrency->value];

        $pairLiquidity = ($fromLiquidity + $toLiquidity) / 2;

        $spreadPercent = self::BASE_SPREAD_PERCENT / $pairLiquidity;

        $spread = $price * ($spreadPercent / 100);

        return number_format($spread, 2, '.', '');
    }
}
