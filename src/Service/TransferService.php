<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transaction;
use App\Enum\Currency;
use App\Exception\InsufficientFundsException;
use App\Exception\WalletBlockedException;
use App\Exception\WalletNotFoundException;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use DateTimeImmutable;

readonly class TransferService
{
    private const float ANTI_FRAUD_LIMIT_EUR = 15_000.0;

    public function __construct(
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private ExchangeRateService $exchangeRateService,
        private SpreadService $spreadService,
    ) {
    }

    public function transfer(
        int $userId,
        int $fromWalletId,
        int $toWalletId,
        string $fromAmount,
    ): Transaction {
        $fromWallet = $this->walletRepository->findById($fromWalletId);
        if (null === $fromWallet || $fromWallet->getUserId() !== $userId) {
            throw new WalletNotFoundException($fromWalletId);
        }

        $toWallet = $this->walletRepository->findById($toWalletId);
        if (null === $toWallet || $toWallet->getUserId() !== $userId) {
            throw new WalletNotFoundException($toWalletId);
        }

        if ($fromWallet->isBlocked()) {
            throw new WalletBlockedException($fromWalletId);
        }

        if ($toWallet->isBlocked()) {
            throw new WalletBlockedException($toWalletId);
        }

        if ((float) $fromAmount > $fromWallet->getBalance()) {
            throw new InsufficientFundsException($fromWalletId);
        }

        $fromCurrency = $fromWallet->getCurrency();
        $toCurrency = $toWallet->getCurrency();

        $exchangeRate = $this->exchangeRateService->getExchangeRateBetween($fromCurrency, $toCurrency);
        $rawToAmount = (float) $fromAmount * $exchangeRate;
        $spread = $this->spreadService->calculateSpread($rawToAmount, $fromCurrency, $toCurrency);
        $toAmount = $rawToAmount - (float) $spread;

        $toAmountFormatted = number_format($toAmount, 4, '.', '');

        $fromWallet->setBalance($fromWallet->getBalance() - (float) $fromAmount);
        $fromWallet->setLastActivityAt(new DateTimeImmutable());
        $this->walletRepository->save($fromWallet);

        $transaction = Transaction::create(
            fromWalletId: $fromWalletId,
            toWalletId: $toWalletId,
            fromAmount: $fromAmount,
            toAmount: $toAmountFormatted,
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            spread: $spread,
            exchangeRate: number_format($exchangeRate, 6, '.', ''),
            requiresAntiFraudCheck: $this->exceedsAntiFraudLimit($fromAmount, $fromCurrency),
        );

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    private function exceedsAntiFraudLimit(string $fromAmount, Currency $fromCurrency): bool
    {
        $amountInEur = (float) $fromAmount * $this->exchangeRateService->getExchangeRateBetween($fromCurrency, Currency::EUR);

        return $amountInEur > self::ANTI_FRAUD_LIMIT_EUR;
    }
}
