<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserToken;

interface UserTokenRepositoryInterface
{
    public function findById(int $id): ?UserToken;

    public function findByToken(string $token): ?UserToken;

    /** @return UserToken[] */
    public function findByUserId(int $userId): array;

    public function save(UserToken $token): void;

    public function delete(UserToken $token): void;
}
