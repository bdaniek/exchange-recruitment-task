<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\UserToken;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserTokenTest extends TestCase
{
    public function testGetters(): void
    {
        $expiresAt = new DateTimeImmutable('2099-01-01 00:00:00');
        $token = UserToken::create(userId: 7, expiresAt: $expiresAt);

        $this->assertNull($token->getId());
        $this->assertSame(7, $token->getUserId());
        $this->assertSame(64, strlen($token->getToken()));
        $this->assertSame($expiresAt, $token->getExpiresAt());
    }

    public function testCreateGeneratesHexToken(): void
    {
        $token = UserToken::create(userId: 1, expiresAt: new DateTimeImmutable('2099-01-01 00:00:00'));

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->getToken());
    }

    public function testIsExpiredReturnsTrueForPastDate(): void
    {
        $token = UserToken::create(userId: 1, expiresAt: new DateTimeImmutable('2020-01-01 00:00:00'));

        $this->assertTrue($token->isExpired());
    }

    public function testIsExpiredReturnsFalseForFutureDate(): void
    {
        $token = UserToken::create(userId: 1, expiresAt: new DateTimeImmutable('2099-01-01 00:00:00'));

        $this->assertFalse($token->isExpired());
    }

    public function testIsValidReturnsFalseForExpiredToken(): void
    {
        $token = UserToken::create(userId: 1, expiresAt: new DateTimeImmutable('2020-01-01 00:00:00'));

        $this->assertFalse($token->isValid());
    }

    public function testIsValidReturnsTrueForNonExpiredToken(): void
    {
        $token = UserToken::create(userId: 1, expiresAt: new DateTimeImmutable('2099-01-01 00:00:00'));

        $this->assertTrue($token->isValid());
    }
}
