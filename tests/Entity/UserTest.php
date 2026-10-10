<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetters(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 08:00:00');
        $user = new User(
            id: 42,
            email: 'test@example.com',
            roles: ['ROLE_USER'],
            createdAt: $createdAt,
        );

        $this->assertSame(42, $user->getId());
        $this->assertSame(42, $user->getIdNotNull());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
        $this->assertSame($createdAt, $user->getCreatedAt());
    }

    public function testSetRoles(): void
    {
        $user = new User(
            id: null,
            email: 'test@example.com',
            roles: [],
            createdAt: new DateTimeImmutable('2024-01-15 08:00:00'),
        );
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
