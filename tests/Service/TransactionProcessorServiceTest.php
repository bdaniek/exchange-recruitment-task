<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use App\Service\TransactionProcessorService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TransactionProcessorServiceTest extends TestCase
{
    private WalletRepositoryInterface $walletRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private TransactionProcessorService $transactionProcessorService;

    protected function setUp(): void
    {
        $this->walletRepository = $this->createMock(WalletRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);

        $this->transactionProcessorService = new TransactionProcessorService(
            $this->walletRepository,
            $this->transactionRepository,
        );
    }

    public function testCompleteUpdatesWalletBalancesAndSetsCompletedStatus(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(500.0);

        $toWallet = Wallet::create(1, Currency::EUR);
        $toWallet->setBalance(100.0);

        $transaction = $this->makeTransaction(requiresAntiFraudCheck: false);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, $toWallet],
            ]);

        $this->walletRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->with($this->isInstanceOf(Wallet::class));

        $this->transactionRepository
            ->expects(self::once())
            ->method('save')
            ->with($transaction);

        $this->transactionProcessorService->complete($transaction);

        self::assertSame(400.0, $fromWallet->getBalance());
        self::assertSame(125.0, $toWallet->getBalance());
        self::assertNotNull($fromWallet->getLastActivityAt());
        self::assertNotNull($toWallet->getLastActivityAt());
        self::assertSame(TransactionStatus::COMPLETED, $transaction->getStatus());
        self::assertNull($transaction->getAntiFraudCheckedAt());
    }

    public function testCompleteSetsAntiFraudCheckedAtWhenRequired(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(500.0);

        $toWallet = Wallet::create(1, Currency::EUR);

        $transaction = $this->makeTransaction(requiresAntiFraudCheck: true);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, $toWallet],
            ]);

        $this->transactionProcessorService->complete($transaction);

        self::assertSame(TransactionStatus::COMPLETED, $transaction->getStatus());
        self::assertNotNull($transaction->getAntiFraudCheckedAt());
    }

    public function testCompleteRejectsWhenFromWalletNotFound(): void
    {
        $transaction = $this->makeTransaction(requiresAntiFraudCheck: false);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, null],
                [2, Wallet::create(1, Currency::EUR)],
            ]);

        $this->walletRepository->expects(self::never())->method('save');

        $this->transactionProcessorService->complete($transaction);

        self::assertSame(TransactionStatus::REJECTED, $transaction->getStatus());
    }

    public function testCompleteRejectsWhenToWalletNotFound(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(500.0);

        $transaction = $this->makeTransaction(requiresAntiFraudCheck: false);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, null],
            ]);

        $this->walletRepository->expects(self::never())->method('save');

        $this->transactionProcessorService->complete($transaction);

        self::assertSame(TransactionStatus::REJECTED, $transaction->getStatus());
    }

    public function testRejectSetsRejectedStatus(): void
    {
        $transaction = $this->makeTransaction(requiresAntiFraudCheck: false);

        $wallet = $this->createStub(Wallet::class);

        $this->transactionRepository
            ->expects(self::once())
            ->method('save')
            ->with($transaction);
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($wallet);
        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->with($wallet);

        $this->transactionProcessorService->reject($transaction);

        self::assertSame(TransactionStatus::REJECTED, $transaction->getStatus());
        self::assertNull($transaction->getAntiFraudCheckedAt());
    }

    public function testRejectSetsAntiFraudCheckedAtWhenRequired(): void
    {
        $transaction = $this->makeTransaction(requiresAntiFraudCheck: true);

        $this->transactionProcessorService->reject($transaction);

        self::assertSame(TransactionStatus::REJECTED, $transaction->getStatus());
        self::assertNotNull($transaction->getAntiFraudCheckedAt());
    }

    private function makeTransaction(bool $requiresAntiFraudCheck): Transaction
    {
        return Transaction::create(
            fromWalletId: 1,
            toWalletId: 2,
            fromAmount: '100.0000',
            toAmount: '25.0000',
            fromCurrency: Currency::PLN,
            toCurrency: Currency::EUR,
            spread: '0.5000',
            exchangeRate: '0.250000',
            requiresAntiFraudCheck: $requiresAntiFraudCheck,
        );
    }
}
