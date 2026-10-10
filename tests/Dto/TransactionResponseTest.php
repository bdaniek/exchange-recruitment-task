<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\TransactionResponse;
use App\Entity\Transaction;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class TransactionResponseTest extends TestCase
{
    private function makeTransaction(TransactionStatus $status = TransactionStatus::PENDING): Transaction
    {
        return new Transaction(
            id: 7,
            fromWalletId: 1,
            toWalletId: 2,
            fromAmount: '100.0000',
            toAmount: '25.1234',
            fromCurrency: Currency::PLN,
            toCurrency: Currency::EUR,
            spread: '0.1260',
            exchangeRate: '0.250000',
            status: $status,
            requiresAntiFraudCheck: false,
            antiFraudCheckedAt: null,
            createdAt: new DateTimeImmutable('2026-01-15T10:00:00+00:00'),
        );
    }

    public function testJsonSerializeReturnsAllFields(): void
    {
        $data = new TransactionResponse($this->makeTransaction())->jsonSerialize();

        self::assertSame(7, $data['id']);
        self::assertSame(1, $data['fromWalletId']);
        self::assertSame(2, $data['toWalletId']);
        self::assertSame('100.0000', $data['fromAmount']);
        self::assertSame('25.1234', $data['toAmount']);
        self::assertSame('PLN', $data['fromCurrency']);
        self::assertSame('EUR', $data['toCurrency']);
        self::assertSame('0.1260', $data['spread']);
        self::assertSame('0.250000', $data['exchangeRate']);
        self::assertSame('pending', $data['status']);
        self::assertSame(
            new DateTimeImmutable('2026-01-15T10:00:00+00:00')->format(DateTimeInterface::ATOM),
            $data['createdAt'],
        );
    }

    public function testJsonSerializeWithFraudReviewStatus(): void
    {
        $data = new TransactionResponse($this->makeTransaction(TransactionStatus::FRAUD_REVIEW))->jsonSerialize();

        self::assertSame('fraud_review', $data['status']);
    }

    public function testJsonSerializeWithNullId(): void
    {
        $transaction = new Transaction(
            id: null,
            fromWalletId: 3,
            toWalletId: 4,
            fromAmount: '50.0000',
            toAmount: '50.0000',
            fromCurrency: Currency::USD,
            toCurrency: Currency::USD,
            spread: '0.0000',
            exchangeRate: '1.000000',
            status: TransactionStatus::PENDING,
            requiresAntiFraudCheck: false,
            antiFraudCheckedAt: null,
            createdAt: new DateTimeImmutable(),
        );

        $data = new TransactionResponse($transaction)->jsonSerialize();

        self::assertNull($data['id']);
    }
}
