<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Currency;
use App\Enum\TransactionStatus;
use DateTimeImmutable;

class Transaction
{
    public function __construct(
        private ?int $id,
        private readonly int $fromWalletId,
        private readonly int $toWalletId,
        private readonly string $fromAmount,
        private readonly string $toAmount,
        private readonly Currency $fromCurrency,
        private readonly Currency $toCurrency,
        private readonly string $spread,
        private readonly string $exchangeRate,
        private TransactionStatus $status,
        private readonly bool $requiresAntiFraudCheck,
        private ?DateTimeImmutable $antiFraudCheckedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        int $fromWalletId,
        int $toWalletId,
        string $fromAmount,
        string $toAmount,
        Currency $fromCurrency,
        Currency $toCurrency,
        string $spread,
        string $exchangeRate,
        bool $requiresAntiFraudCheck,
    ): self {
        return new self(
            id: null,
            fromWalletId: $fromWalletId,
            toWalletId: $toWalletId,
            fromAmount: $fromAmount,
            toAmount: $toAmount,
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            spread: $spread,
            exchangeRate: $exchangeRate,
            status: $requiresAntiFraudCheck
                ? TransactionStatus::FRAUD_REVIEW
                : TransactionStatus::PENDING,
            requiresAntiFraudCheck: $requiresAntiFraudCheck,
            antiFraudCheckedAt: null,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromWalletId(): int
    {
        return $this->fromWalletId;
    }

    public function getToWalletId(): int
    {
        return $this->toWalletId;
    }

    public function getFromAmount(): string
    {
        return $this->fromAmount;
    }

    public function getToAmount(): string
    {
        return $this->toAmount;
    }

    public function getFromCurrency(): Currency
    {
        return $this->fromCurrency;
    }

    public function getToCurrency(): Currency
    {
        return $this->toCurrency;
    }

    public function getSpread(): string
    {
        return $this->spread;
    }

    public function getExchangeRate(): string
    {
        return $this->exchangeRate;
    }

    public function getStatus(): TransactionStatus
    {
        return $this->status;
    }

    public function requiresAntiFraudCheck(): bool
    {
        return $this->requiresAntiFraudCheck;
    }

    public function getAntiFraudCheckedAt(): ?DateTimeImmutable
    {
        return $this->antiFraudCheckedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setStatus(TransactionStatus $status): void
    {
        $this->status = $status;
    }

    public function setAntiFraudCheckedAt(?DateTimeImmutable $antiFraudCheckedAt): void
    {
        $this->antiFraudCheckedAt = $antiFraudCheckedAt;
    }
}
