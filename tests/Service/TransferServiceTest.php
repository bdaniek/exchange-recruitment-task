<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use App\Exception\WalletNotFoundException;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use App\Service\ExchangeRateService;
use App\Service\SpreadService;
use App\Service\TransferService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TransferServiceTest extends TestCase
{
    private WalletRepositoryInterface $walletRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private ExchangeRateService $exchangeRateService;
    private SpreadService $spreadService;
    private TransferService $transferService;

    protected function setUp(): void
    {
        $this->walletRepository = $this->createMock(WalletRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);
        $this->exchangeRateService = $this->createMock(ExchangeRateService::class);
        $this->spreadService = $this->createMock(SpreadService::class);

        $this->transferService = new TransferService(
            $this->walletRepository,
            $this->transactionRepository,
            $this->exchangeRateService,
            $this->spreadService,
        );
    }

    public function testTransferSuccessfully(): void
    {
        $userId = 1;
        $fromWallet = $this->createMock(Wallet::class);
        $fromWallet
            ->method('getCurrency')
            ->willReturn(Currency::PLN);
        $fromWallet
            ->expects($this->atLeastOnce())
            ->method('getBalance')
            ->willReturnOnConsecutiveCalls(
                5000.0,
                4000.0
            );
        $fromWallet
            ->method('getUserId')
            ->willReturn($userId);
        $fromWallet
            ->expects($this->never())
            ->method('setBalance');
        $toWallet = $this->createMock(Wallet::class);
        $toWallet
            ->method('getCurrency')
            ->willReturn(Currency::EUR);
        $toWallet
            ->expects($this->atLeastOnce())
            ->method('getBalance')
            ->willReturnOnConsecutiveCalls(
                100.0,
                349.0
            );
        $toWallet
            ->method('getUserId')
            ->willReturn($userId);
        $toWallet
            ->expects($this->never())
            ->method('setBalance');

        $this->walletRepository
            ->expects(self::exactly(2))
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, $toWallet],
            ]);

        $this->exchangeRateService
            ->expects(self::once())
            ->method('getExchangeRateBetween')
            ->with(Currency::PLN, Currency::EUR)
            ->willReturn(0.25);

        $this->spreadService
            ->expects(self::once())
            ->method('calculateSpread')
            ->with(250.0, Currency::PLN, Currency::EUR)
            ->willReturn('1.00');

        $this->walletRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->with($this->isInstanceOf(Wallet::class));

        $this->transactionRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->isInstanceOf(Transaction::class));

        $transaction = $this->transferService->transfer($userId, 1, 2, '1000.00');

        self::assertSame(4000.0, $fromWallet->getBalance());
        self::assertSame(349.0, $toWallet->getBalance());
        self::assertSame(TransactionStatus::PENDING, $transaction->getStatus());
        self::assertFalse($transaction->requiresAntiFraudCheck());
        self::assertSame('1000.00', $transaction->getFromAmount());
        self::assertSame('248.3775', $transaction->getToAmount());
        self::assertSame('0.250000', $transaction->getExchangeRate());
        self::assertSame('1.00', $transaction->getSpread());
        self::assertSame(Currency::PLN, $transaction->getFromCurrency());
        self::assertSame(Currency::EUR, $transaction->getToCurrency());
    }

    public function testTransferThrowsWhenFromWalletNotFound(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 99 not found.');

        $this->transferService->transfer(1, 99, 2, '100.00');
    }

    public function testTransferThrowsWhenToWalletNotFound(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [99, null],
            ]);

        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 99 not found.');

        $this->transferService->transfer(1, 1, 99, '100.00');
    }

    public function testTransferThrowsWhenFromWalletBelongsToOtherUser(): void
    {
        $fromWallet = Wallet::create(2, Currency::PLN);

        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($fromWallet);

        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 1 not found.');

        $this->transferService->transfer(1, 1, 2, '100.00');
    }

    public function testTransferThrowsWhenToWalletBelongsToOtherUser(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $toWallet = Wallet::create(2, Currency::EUR);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, $toWallet],
            ]);

        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 2 not found.');

        $this->transferService->transfer(1, 1, 2, '100.00');
    }
}
