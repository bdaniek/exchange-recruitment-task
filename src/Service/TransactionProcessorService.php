<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transaction;
use App\Enum\TransactionStatus;
use App\Repository\CompanyWalletRepositoryInterface;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final readonly class TransactionProcessorService
{
    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private CompanyWalletRepositoryInterface $companyWalletRepository,
        private Connection $connection,
    ) {
    }

    public function complete(Transaction $transaction): void
    {
        $this->connection->transactional(function () use ($transaction): void {
            $this->walletRepository->lockByIds($transaction->getFromWalletId(), $transaction->getToWalletId());

            $fromWallet = $this->walletRepository->findById($transaction->getFromWalletId());
            $toWallet = $this->walletRepository->findById($transaction->getToWalletId());

            if (null === $fromWallet || null === $toWallet) {
                $this->reject($transaction);

                return;
            }

            $toWallet->setBalance($toWallet->getBalance() + (float) $transaction->getToAmount());
            $toWallet->setLastActivityAt(new DateTimeImmutable());
            $this->walletRepository->save($toWallet);

            if ((float) $transaction->getSpread() > 0) {
                $this->companyWalletRepository->addToBalance($transaction->getToCurrency(), $transaction->getSpread());
            }

            $transaction->setStatus(TransactionStatus::COMPLETED);

            if ($transaction->requiresAntiFraudCheck()) {
                $transaction->setAntiFraudCheckedAt(new DateTimeImmutable());
            }

            $this->transactionRepository->save($transaction);
        });
    }

    public function reject(Transaction $transaction): void
    {
        $this->connection->transactional(function () use ($transaction): void {
            $this->walletRepository->lockByIds($transaction->getFromWalletId());

            $fromWallet = $this->walletRepository->findById($transaction->getFromWalletId());
            if (null !== $fromWallet) {
                $fromWallet->setBalance($fromWallet->getBalance() + (float) $transaction->getFromAmount());
                $fromWallet->setLastActivityAt(new DateTimeImmutable());
                $this->walletRepository->save($fromWallet);
            }

            $transaction->setStatus(TransactionStatus::REJECTED);

            if ($transaction->requiresAntiFraudCheck()) {
                $transaction->setAntiFraudCheckedAt(new DateTimeImmutable());
            }

            $this->transactionRepository->save($transaction);
        });
    }
}
