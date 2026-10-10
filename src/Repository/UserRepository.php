<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use ReflectionClass;

readonly class UserRepository implements UserRepositoryInterface
{
    private const string TABLE_NAME = 'users';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function findById(int $id): ?User
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('id = :id');

        $row = $this->connection->fetchAssociative($qb->getSQL(), ['id' => $id]);

        if (!$row) {
            return null;
        }

        return $this->buildEntity($row);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function findByEmail(string $email): ?User
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('email = :email');

        $row = $this->connection->fetchAssociative($qb->getSQL(), ['email' => $email]);

        if (!$row) {
            return null;
        }

        return $this->buildEntity($row);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function save(User $user): void
    {
        if (null === $user->getId()) {
            $this->insert($user);
        } else {
            $this->update($user);
        }
    }

    private function buildEntity(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            email: $row['email'],
            roles: json_decode(json: $row['roles'], associative: true, flags: JSON_THROW_ON_ERROR),
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function insert(User $user): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->insert(self::TABLE_NAME)
            ->values([
                'email' => ':email',
                'roles' => ':roles',
                'created_at' => ':created_at',
            ]);

        $this->connection->executeQuery(
            $qb->getSQL(),
            [
                'email' => $user->getUserIdentifier(),
                'roles' => json_encode(value: $user->getRoles(), flags: JSON_THROW_ON_ERROR),
                'created_at' => $user->getCreatedAt()
                    ->setTimezone(timezone: new DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s'),
            ]
        );

        $id = (int) $this->connection->lastInsertId();

        $reflectionClass = new ReflectionClass($user);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setValue(objectOrValue: $user, value: $id);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    private function update(User $user): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update(self::TABLE_NAME)
            ->set('roles', ':roles')
            ->where('id = :id');

        $this->connection->executeQuery(
            $qb->getSQL(),
            [
                'roles' => json_encode(value: $user->getRoles(), flags: JSON_THROW_ON_ERROR),
                'id' => $user->getId(),
            ]
        );
    }
}
