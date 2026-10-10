<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Currency;
use DateTimeImmutable;

class CompanyWallet
{
    public function __construct(
        private ?int $id,
        private readonly Currency $currency,
        private float $balance,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(Currency $currency): self
    {
        $now = new DateTimeImmutable();

        return new self(
            id: null,
            currency: $currency,
            balance: 0.0,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }
}
