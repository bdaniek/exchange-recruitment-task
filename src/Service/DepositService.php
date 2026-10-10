<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Wallet;
use App\Exception\WalletBlockedException;
use App\Exception\WalletNotFoundException;
use App\Repository\WalletRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

readonly class DepositService
{
    public const float MAX_AMOUNT = 10000.0;

    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private Connection $connection,
    ) {
    }

    public function deposit(int $userId, int $walletId, string $amount): Wallet
    {
        return $this->connection->transactional(function () use ($userId, $walletId, $amount): Wallet {
            $this->walletRepository->lockByIds($walletId);

            $wallet = $this->walletRepository->findById($walletId);
            if (null === $wallet || $wallet->getUserId() !== $userId) {
                throw new WalletNotFoundException($walletId);
            }

            if ($wallet->isBlocked()) {
                throw new WalletBlockedException($walletId);
            }

            $wallet->setBalance($wallet->getBalance() + (float) $amount);
            $wallet->setLastActivityAt(new DateTimeImmutable());
            $this->walletRepository->save($wallet);

            return $wallet;
        });
    }
}
