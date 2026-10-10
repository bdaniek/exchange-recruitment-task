<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\Currency;
use App\Service\SpreadService;
use Generator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SpreadServiceTest extends TestCase
{
    private SpreadService $spreadService;

    protected function setUp(): void
    {
        $this->spreadService = new SpreadService();
    }

    #[DataProvider('calculateSpreadDataProvider')]
    public function testCalculateSpread(
        float $price,
        Currency $fromCurrency,
        Currency $toCurrency,
        string $expectedResult,
    ): void {
        $result = $this->spreadService->calculateSpread($price, $fromCurrency, $toCurrency);

        self::assertSame($expectedResult, $result);
    }

    public static function calculateSpreadDataProvider(): Generator
    {
        yield 'USD to USD (same currency, no conversion)' => [
            'price' => 100.0,
            'fromCurrency' => Currency::USD,
            'toCurrency' => Currency::USD,
            'expectedResult' => '0.00',
        ];
        yield 'PLN to PLN (same currency, no conversion)' => [
            'price' => 100.0,
            'fromCurrency' => Currency::PLN,
            'toCurrency' => Currency::PLN,
            'expectedResult' => '0.00',
        ];
        yield 'HUF to HUF (same currency, no conversion)' => [
            'price' => 100.0,
            'fromCurrency' => Currency::HUF,
            'toCurrency' => Currency::HUF,
            'expectedResult' => '0.00',
        ];
        yield 'USD to EUR' => [
            'price' => 100.0,
            'fromCurrency' => Currency::USD,
            'toCurrency' => Currency::EUR,
            'expectedResult' => '0.51',
        ];
        yield 'GBP to CHF' => [
            'price' => 100.0,
            'fromCurrency' => Currency::GBP,
            'toCurrency' => Currency::CHF,
            'expectedResult' => '0.61',
        ];
        yield 'HUF to JPY' => [
            'price' => 100.0,
            'fromCurrency' => Currency::HUF,
            'toCurrency' => Currency::JPY,
            'expectedResult' => '0.87',
        ];
        yield 'HUF to PLN' => [
            'price' => 50.0,
            'fromCurrency' => Currency::HUF,
            'toCurrency' => Currency::PLN,
            'expectedResult' => '0.53',
        ];
        yield 'USD to EUR with higher price' => [
            'price' => 200.0,
            'fromCurrency' => Currency::USD,
            'toCurrency' => Currency::EUR,
            'expectedResult' => '1.03',
        ];
    }
}
