<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Transaction;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testGetters(): void
    {
        $transaction = $this->makeTransaction();

        $this->assertNull($transaction->getId());
        $this->assertSame(1, $transaction->getFromWalletId());
        $this->assertSame(2, $transaction->getToWalletId());
        $this->assertSame('100.00', $transaction->getFromAmount());
        $this->assertSame('90.00', $transaction->getToAmount());
        $this->assertSame(Currency::PLN, $transaction->getFromCurrency());
        $this->assertSame(Currency::EUR, $transaction->getToCurrency());
        $this->assertSame('0.02', $transaction->getSpread());
        $this->assertSame('0.22', $transaction->getExchangeRate());
        $this->assertSame(TransactionStatus::PENDING, $transaction->getStatus());
        $this->assertFalse($transaction->requiresAntiFraudCheck());
        $this->assertNull($transaction->getAntiFraudCheckedAt());
    }

    public function testCreateSetsPendingStatus(): void
    {
        $transaction = $this->makeTransaction(requiresAntiFraudCheck: false);

        $this->assertSame(TransactionStatus::PENDING, $transaction->getStatus());
    }

    public function testCreateSetsFraudReviewStatus(): void
    {
        $transaction = $this->makeTransaction(requiresAntiFraudCheck: true);

        $this->assertSame(TransactionStatus::FRAUD_REVIEW, $transaction->getStatus());
    }

    public function testSetStatus(): void
    {
        $transaction = $this->makeTransaction();
        $transaction->setStatus(TransactionStatus::COMPLETED);

        $this->assertSame(TransactionStatus::COMPLETED, $transaction->getStatus());
    }

    public function testSetAntiFraudCheckedAt(): void
    {
        $transaction = $this->makeTransaction();
        $date = new DateTimeImmutable('2024-01-01 12:00:00');
        $transaction->setAntiFraudCheckedAt($date);

        $this->assertSame($date, $transaction->getAntiFraudCheckedAt());
    }

    public function testSetAntiFraudCheckedAtWithNull(): void
    {
        $transaction = $this->makeTransaction();
        $transaction->setAntiFraudCheckedAt(new DateTimeImmutable('2024-01-01 12:00:00'));
        $transaction->setAntiFraudCheckedAt(null);

        $this->assertNull($transaction->getAntiFraudCheckedAt());
    }

    private function makeTransaction(bool $requiresAntiFraudCheck = false): Transaction
    {
        return Transaction::create(
            fromWalletId: 1,
            toWalletId: 2,
            fromAmount: '100.00',
            toAmount: '90.00',
            fromCurrency: Currency::PLN,
            toCurrency: Currency::EUR,
            spread: '0.02',
            exchangeRate: '0.22',
            requiresAntiFraudCheck: $requiresAntiFraudCheck,
        );
    }
}
