<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Wallet;
use App\Enum\Currency;
use App\Exception\WalletAlreadyExistsException;
use App\Repository\WalletRepositoryInterface;
use App\Service\WalletService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WalletServiceTest extends TestCase
{
    private WalletRepositoryInterface $walletRepository;
    private WalletService $walletService;

    protected function setUp(): void
    {
        $this->walletRepository = $this->createMock(WalletRepositoryInterface::class);
        $this->walletService = new WalletService($this->walletRepository);
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
}
