<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

class UserToken
{
    public function __construct(
        private ?int $id,
        private readonly int $userId,
        private readonly string $token,
        private readonly DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(int $userId, DateTimeImmutable $expiresAt): self
    {
        return new self(
            id: null,
            userId: $userId,
            token: bin2hex(random_bytes(32)),
            expiresAt: $expiresAt,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }
}
