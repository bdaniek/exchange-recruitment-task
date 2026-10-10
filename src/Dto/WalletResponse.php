<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Wallet;
use DateTimeInterface;
use JsonSerializable;

final readonly class WalletResponse implements JsonSerializable
{
    public function __construct(private Wallet $wallet)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->wallet->getId(),
            'currency' => $this->wallet->getCurrency()->value,
            'balance' => $this->wallet->getBalance(),
            'isBlocked' => $this->wallet->isBlocked(),
            'lastActivityAt' => $this->wallet->getLastActivityAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
