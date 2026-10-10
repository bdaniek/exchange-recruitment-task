<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Transaction;
use DateTimeInterface;
use JsonSerializable;

final readonly class TransactionResponse implements JsonSerializable
{
    public function __construct(private Transaction $transaction)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->transaction->getId(),
            'fromWalletId' => $this->transaction->getFromWalletId(),
            'toWalletId' => $this->transaction->getToWalletId(),
            'fromAmount' => $this->transaction->getFromAmount(),
            'toAmount' => $this->transaction->getToAmount(),
            'fromCurrency' => $this->transaction->getFromCurrency()->value,
            'toCurrency' => $this->transaction->getToCurrency()->value,
            'spread' => $this->transaction->getSpread(),
            'exchangeRate' => $this->transaction->getExchangeRate(),
            'status' => $this->transaction->getStatus()->value,
            'createdAt' => $this->transaction->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
