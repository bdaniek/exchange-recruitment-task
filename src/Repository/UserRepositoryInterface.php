<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;
}
