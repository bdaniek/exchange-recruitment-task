<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Wallet;
use App\Enum\Currency;
use App\Exception\WalletBlockedException;
use App\Exception\WalletNotFoundException;
use App\Repository\WalletRepositoryInterface;
use App\Service\DepositService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DepositServiceTest extends TestCase
{
    private WalletRepositoryInterface $walletRepository;
    private DepositService $depositService;

    protected function setUp(): void
    {
        $this->walletRepository = $this->createMock(WalletRepositoryInterface::class);
        $this->depositService = new DepositService($this->walletRepository);
    }

    public function testDepositSuccessfully(): void
    {
        $userId = 1;
        $wallet = Wallet::create($userId, Currency::PLN);

        $this->walletRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($wallet);

        $this->walletRepository
            ->expects(self::once())
            ->method('save')
            ->with($wallet);

        $result = $this->depositService->deposit($userId, 1, '500.00');

        self::assertSame(500.0, $result->getBalance());
        self::assertNotNull($result->getLastActivityAt());
    }

    public function testDepositAddsToExistingBalance(): void
    {
        $userId = 1;
        $wallet = Wallet::create($userId, Currency::EUR);
        $wallet->setBalance(200.0);

        $this->walletRepository
            ->method('findById')
            ->willReturn($wallet);

        $this->depositService->deposit($userId, 1, '300.00');

        self::assertSame(500.0, $wallet->getBalance());
    }

    public function testDepositThrowsWhenWalletNotFound(): void
    {
        $this->walletRepository
            ->expects(self::once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 99 not found.');

        $this->depositService->deposit(1, 99, '100.00');
    }

    public function testDepositThrowsWhenWalletBelongsToOtherUser(): void
    {
        $wallet = Wallet::create(2, Currency::PLN);

        $this->walletRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($wallet);

        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletNotFoundException::class);
        $this->expectExceptionMessage('Wallet 1 not found.');

        $this->depositService->deposit(1, 1, '100.00');
    }

    public function testDepositThrowsWhenWalletIsBlocked(): void
    {
        $userId = 1;
        $wallet = Wallet::create($userId, Currency::PLN);
        $wallet->setIsBlocked(true);

        $this->walletRepository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($wallet);

        $this->walletRepository->expects(self::never())->method('save');

        $this->expectException(WalletBlockedException::class);
        $this->expectExceptionMessage('Wallet 1 is blocked.');

        $this->depositService->deposit($userId, 1, '100.00');
    }
}
