<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use App\Exception\WalletAlreadyExistsException;
use App\Exception\WalletBlockedException;
use App\Exception\WalletCannotBeDeletedException;
use App\Exception\WalletNotFoundException;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use App\Service\WalletService;
use Closure;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WalletServiceTest extends TestCase
{
    private WalletRepositoryInterface $walletRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private WalletService $walletService;

    protected function setUp(): void
    {
        $this->walletRepository = $this->createMock(WalletRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);
        $this->walletService = new WalletService(
            $this->walletRepository,
            $this->transactionRepository,
            $this->createConnection(),
        );
    }

    public function testCreateWalletSuccessfully(): void
    {
        $userId = 1;
        $currency = Currency::EUR;

        $this->walletRepository
            ->expects(self::once())
            ->method('findByUserIdAndCurrency')
            ->with($userId, $currency)
            ->willReturn(null);

        $this->walletRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->isInstanceOf(Wallet::class));

        $wallet = $this->walletService->createWallet($userId, $currency);

        self::assertSame($userId, $wallet->getUserId());
        self::assertSame($currency, $wallet->getCurrency());
        self::assertSame(0.0, $wallet->getBalance());
        self::assertFalse($wallet->isBlocked());
    }

    public function testCreateWalletThrowsWhenWalletAlreadyExists(): void
    {
        $userId = 1;
        $currency = Currency::PLN;

        $existingWallet = Wallet::create($userId, $currency);

        $this->walletRepository
            ->expects(self::once())
            ->method('findByUserIdAndCurrency')
            ->with($userId, $currency)
            ->willReturn($existingWallet);

        $this->walletRepository
            ->expects(self::never())
            ->method('save');

        $this->expectException(WalletAlreadyExistsException::class);
        $this->expectExceptionMessage('Wallet for user 1 in currency PLN already exists.');

        $this->walletService->createWallet($userId, $currency);
    }

    public function testDeleteWalletMarksWalletAsDeleted(): void
    {
        $wallet = Wallet::create(1, Currency::EUR);

        $this->walletRepository
            ->expects(self::once())
            ->method('lockByIds')
            ->with(10);

        $this->walletRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($wallet);

        $this->transactionRepository
            ->method('findByWalletId')
            ->willReturn([$this->makeTransaction(completed: true)]);

        $this->walletRepository
            ->expects(self::once())
            ->method('save')
            ->with($wallet);

        $this->walletService->deleteWallet(1, 10);

        self::assertNotNull($wallet->getDeletedAt());
    }

    public function testDeleteWalletThrowsWhenWalletNotFound(): void
    {
        $this->walletRepository->method('findById')->willReturn(null);
        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 10 not found.');

        $this->walletService->deleteWallet(1, 10);
    }

    public function testDeleteWalletThrowsWhenWalletBelongsToOtherUser(): void
    {
        $this->walletRepository->method('findById')->willReturn(Wallet::create(2, Currency::EUR));
        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);

        $this->walletService->deleteWallet(1, 10);
    }

    public function testDeleteWalletThrowsWhenWalletIsBlocked(): void
    {
        $wallet = Wallet::create(1, Currency::EUR);
        $wallet->setIsBlocked(true);
        $this->walletRepository->method('findById')->willReturn($wallet);
        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletBlockedException::class);

        $this->walletService->deleteWallet(1, 10);
    }

    public function testDeleteWalletThrowsWhenBalanceIsNotZero(): void
    {
        $wallet = Wallet::create(1, Currency::EUR);
        $wallet->setBalance(0.0001);
        $this->walletRepository->method('findById')->willReturn($wallet);
        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletCannotBeDeletedException::class);
        $this->expectExceptionMessage('Wallet 10 cannot be deleted: balance is not zero.');

        $this->walletService->deleteWallet(1, 10);
    }

    public function testDeleteWalletThrowsWhenWalletHasUnprocessedTransactions(): void
    {
        $this->walletRepository->method('findById')->willReturn(Wallet::create(1, Currency::EUR));
        $this->transactionRepository
            ->method('findByWalletId')
            ->willReturn([$this->makeTransaction(completed: true), $this->makeTransaction(completed: false)]);
        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletCannotBeDeletedException::class);
        $this->expectExceptionMessage('Wallet 10 cannot be deleted: it has unprocessed transactions.');

        $this->walletService->deleteWallet(1, 10);
    }

    public function testDeleteWalletThrowsWhenWalletHasFraudReviewTransaction(): void
    {
        $this->walletRepository->method('findById')->willReturn(Wallet::create(1, Currency::EUR));
        $this->transactionRepository
            ->method('findByWalletId')
            ->willReturn([Transaction::create(
                fromWalletId: 11,
                toWalletId: 10,
                fromAmount: '70000.0000',
                toAmount: '16400.0000',
                fromCurrency: Currency::PLN,
                toCurrency: Currency::EUR,
                spread: '110.00',
                exchangeRate: '0.235910',
                requiresAntiFraudCheck: true,
            )]);
        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletCannotBeDeletedException::class);

        $this->walletService->deleteWallet(1, 10);
    }

    private function makeTransaction(bool $completed): Transaction
    {
        $transaction = Transaction::create(
            fromWalletId: 10,
            toWalletId: 11,
            fromAmount: '100.0000',
            toAmount: '25.0000',
            fromCurrency: Currency::EUR,
            toCurrency: Currency::PLN,
            spread: '0.10',
            exchangeRate: '4.238900',
            requiresAntiFraudCheck: false,
        );

        if ($completed) {
            $transaction->setStatus(TransactionStatus::COMPLETED);
        }

        return $transaction;
    }

    private function createConnection(): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('transactional')
            ->willReturnCallback(static fn (Closure $operation): mixed => $operation($connection));

        return $connection;
    }
}
