<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Wallet;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use App\Exception\WalletAlreadyExistsException;
use App\Exception\WalletBlockedException;
use App\Exception\WalletCannotBeDeletedException;
use App\Exception\WalletNotFoundException;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use Doctrine\DBAL\Connection;

readonly class WalletService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private Connection $connection,
    ) {
    }

    public function createWallet(int $userId, Currency $currency): Wallet
    {
        $existing = $this->walletRepository->findByUserIdAndCurrency($userId, $currency);

        if (null !== $existing) {
            throw new WalletAlreadyExistsException($userId, $currency);
        }

        $wallet = Wallet::create($userId, $currency);
        $this->walletRepository->save($wallet);

        return $wallet;
    }

    public function deleteWallet(int $userId, int $walletId): void
    {
        $this->connection->transactional(function () use ($userId, $walletId): void {
            $this->walletRepository->lockByIds($walletId);

            $wallet = $this->walletRepository->findById($walletId);
            if (null === $wallet || $wallet->getUserId() !== $userId) {
                throw new WalletNotFoundException($walletId);
            }

            if ($wallet->isBlocked()) {
                throw new WalletBlockedException($walletId);
            }

            if (0.0 !== $wallet->getBalance()) {
                throw new WalletCannotBeDeletedException($walletId, 'balance is not zero');
            }

            foreach ($this->transactionRepository->findByWalletId($walletId) as $transaction) {
                if (in_array($transaction->getStatus(), [TransactionStatus::PENDING, TransactionStatus::FRAUD_REVIEW], true)) {
                    throw new WalletCannotBeDeletedException($walletId, 'it has unprocessed transactions');
                }
            }

            $wallet->markDeleted();
            $this->walletRepository->save($wallet);
        });
    }
}
