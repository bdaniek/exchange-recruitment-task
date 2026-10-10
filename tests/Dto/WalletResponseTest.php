<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\WalletResponse;
use App\Entity\Wallet;
use App\Enum\Currency;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class WalletResponseTest extends TestCase
{
    public function testJsonSerializeWithoutLastActivityAt(): void
    {
        $wallet = Wallet::create(1, Currency::PLN);

        $data = new WalletResponse($wallet)->jsonSerialize();

        self::assertNull($data['id']);
        self::assertSame('PLN', $data['currency']);
        self::assertSame(0.0, $data['balance']);
        self::assertFalse($data['isBlocked']);
        self::assertNull($data['lastActivityAt']);
    }

    public function testJsonSerializeWithLastActivityAt(): void
    {
        $lastActivity = new DateTimeImmutable('2024-06-01T12:00:00+00:00');
        $wallet = Wallet::create(1, Currency::EUR);
        $wallet->setLastActivityAt($lastActivity);

        $data = new WalletResponse($wallet)->jsonSerialize();

        self::assertSame($lastActivity->format(DateTimeInterface::ATOM), $data['lastActivityAt']);
    }

    public function testJsonSerializeWithBlockedWallet(): void
    {
        $wallet = Wallet::create(1, Currency::USD);
        $wallet->setIsBlocked(true);

        $data = new WalletResponse($wallet)->jsonSerialize();

        self::assertTrue($data['isBlocked']);
    }
}
