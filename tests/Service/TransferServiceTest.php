<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Enum\Currency;
use App\Enum\TransactionStatus;
use App\Exception\InsufficientFundsException;
use App\Exception\WalletBlockedException;
use App\Exception\WalletNotFoundException;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\WalletRepositoryInterface;
use App\Service\ExchangeRateService;
use App\Service\SpreadService;
use App\Service\TransferService;
use Closure;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
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
            $this->createConnection(),
        );
    }

    public function testTransferSuccessfully(): void
    {
        $userId = 1;
        $fromWallet = Wallet::create($userId, Currency::PLN);
        $fromWallet->setBalance(5000.0);
        $toWallet = Wallet::create($userId, Currency::EUR);
        $toWallet->setBalance(100.0);

        $this->walletRepository
            ->expects(self::exactly(2))
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, $toWallet],
            ]);

        $this->exchangeRateService
            ->expects(self::exactly(2))
            ->method('getExchangeRateBetween')
            ->with(Currency::PLN, Currency::EUR)
            ->willReturn(0.25);

        $this->spreadService
            ->expects(self::once())
            ->method('calculateSpread')
            ->with(250.0, Currency::PLN, Currency::EUR)
            ->willReturn('1.00');

        $this->walletRepository
            ->expects(self::once())
            ->method('lockByIds')
            ->with(1, 2);

        $this->walletRepository
            ->expects(self::once())
            ->method('save')
            ->with($fromWallet);

        $this->transactionRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->isInstanceOf(Transaction::class));

        $transaction = $this->transferService->transfer($userId, 1, 2, '1000.00');

        self::assertSame(4000.0, $fromWallet->getBalance());
        self::assertSame(100.0, $toWallet->getBalance());
        self::assertNotNull($fromWallet->getLastActivityAt());
        self::assertSame(TransactionStatus::PENDING, $transaction->getStatus());
        self::assertFalse($transaction->requiresAntiFraudCheck());
        self::assertSame('1000.00', $transaction->getFromAmount());
        self::assertSame('249.0000', $transaction->getToAmount());
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

    public function testTransferThrowsWhenFromWalletBlocked(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(500.0);
        $fromWallet->setIsBlocked(true);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, Wallet::create(1, Currency::EUR)],
            ]);

        $this->walletRepository->expects(self::never())->method('save');
        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(WalletBlockedException::class);
        $this->expectExceptionMessage('Wallet 1 is blocked.');

        $this->transferService->transfer(1, 1, 2, '100.00');
    }

    public function testTransferThrowsWhenToWalletBlocked(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(500.0);
        $toWallet = Wallet::create(1, Currency::EUR);
        $toWallet->setIsBlocked(true);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, $toWallet],
            ]);

        $this->walletRepository->expects(self::never())->method('save');
        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(WalletBlockedException::class);
        $this->expectExceptionMessage('Wallet 2 is blocked.');

        $this->transferService->transfer(1, 1, 2, '100.00');
    }

    public function testTransferThrowsWhenBalanceIsTooLow(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(99.99);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, Wallet::create(1, Currency::EUR)],
            ]);

        $this->walletRepository->expects(self::never())->method('save');
        $this->transactionRepository->expects(self::never())->method('save');

        $this->expectException(InsufficientFundsException::class);
        $this->expectExceptionMessage('Insufficient funds in wallet 1.');

        $this->transferService->transfer(1, 1, 2, '100.00');
    }

    public function testTransferAllowsWholeBalance(): void
    {
        $fromWallet = Wallet::create(1, Currency::PLN);
        $fromWallet->setBalance(100.0);

        $this->walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, Wallet::create(1, Currency::EUR)],
            ]);
        $this->exchangeRateService->method('getExchangeRateBetween')->willReturn(0.25);
        $this->spreadService->method('calculateSpread')->willReturn('0.10');

        $this->transferService->transfer(1, 1, 2, '100.00');

        self::assertSame(0.0, $fromWallet->getBalance());
    }

    public static function antiFraudProvider(): iterable
    {
        yield 'EUR exactly at the limit' => [Currency::EUR, Currency::PLN, '15000', false];
        yield 'EUR above the limit' => [Currency::EUR, Currency::PLN, '15000.01', true];
        yield 'PLN below the limit' => [Currency::PLN, Currency::EUR, '63000', false];
        yield 'PLN above the limit' => [Currency::PLN, Currency::EUR, '64000', true];
        yield 'GBP above the limit in EUR' => [Currency::GBP, Currency::EUR, '14000', true];
        yield 'small EUR amount to HUF' => [Currency::EUR, Currency::HUF, '100', false];
    }

    #[DataProvider('antiFraudProvider')]
    public function testAntiFraudLimitIsCheckedInEur(Currency $from, Currency $to, string $amount, bool $expected): void
    {
        $fromWallet = Wallet::create(1, $from);
        $fromWallet->setBalance(1_000_000.0);

        $walletRepository = $this->createStub(WalletRepositoryInterface::class);
        $walletRepository
            ->method('findById')
            ->willReturnMap([
                [1, $fromWallet],
                [2, Wallet::create(1, $to)],
            ]);

        $transferService = new TransferService(
            $walletRepository,
            $this->createStub(TransactionRepositoryInterface::class),
            new ExchangeRateService(),
            new SpreadService(),
            $this->createConnection(),
        );

        $transaction = $transferService->transfer(1, 1, 2, $amount);

        self::assertSame($expected, $transaction->requiresAntiFraudCheck());
        self::assertSame(
            $expected ? TransactionStatus::FRAUD_REVIEW : TransactionStatus::PENDING,
            $transaction->getStatus(),
        );
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
