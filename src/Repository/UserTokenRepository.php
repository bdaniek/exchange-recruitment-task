<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserToken;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ReflectionClass;

readonly class UserTokenRepository implements UserTokenRepositoryInterface
{
    private const string TABLE_NAME = 'user_tokens';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @throws Exception
     */
    public function findById(int $id): ?UserToken
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('id = :id');

        $row = $this->connection->fetchAssociative($qb->getSQL(), ['id' => $id]);

        return false !== $row ? $this->buildEntity($row) : null;
    }

    /**
     * @throws Exception
     */
    public function findByToken(string $token): ?UserToken
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('token = :token');

        $row = $this->connection->fetchAssociative($qb->getSQL(), ['token' => $token]);

        if (!$row) {
            return null;
        }

        return $this->buildEntity($row);
    }

    /**
     * @return UserToken[]
     *
     * @throws Exception
     */
    public function findByUserId(int $userId): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('user_id = :user_id');

        $rows = $this->connection->fetchAllAssociative($qb->getSQL(), ['user_id' => $userId]);

        return array_map($this->buildEntity(...), $rows);
    }

    /**
     * @throws Exception
     */
    public function save(UserToken $token): void
    {
        if (null === $token->getId()) {
            $this->insert($token);
        } else {
            $this->update($token);
        }
    }

    /**
     * @throws Exception
     */
    public function delete(UserToken $token): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->delete(self::TABLE_NAME)
            ->where('id = :id');

        $this->connection->executeQuery($qb->getSQL(), ['id' => $token->getId()]);
    }

    private function buildEntity(array $row): UserToken
    {
        return new UserToken(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            token: $row['token'],
            expiresAt: new DateTimeImmutable($row['expires_at']),
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }

    /**
     * @throws Exception
     */
    private function insert(UserToken $token): void
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->insert(self::TABLE_NAME)
            ->values([
                'user_id' => ':user_id',
                'token' => ':token',
                'expires_at' => ':expires_at',
                'created_at' => ':created_at',
            ]);

        $this->connection->executeQuery(
            $qb->getSQL(),
            [
                'user_id' => $token->getUserId(),
                'token' => $token->getToken(),
                'expires_at' => $token->getExpiresAt()
                    ->setTimezone(timezone: new DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s'),
                'created_at' => $token->getCreatedAt()
                    ->setTimezone(timezone: new DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s'),
            ]
        );

        $id = (int) $this->connection->lastInsertId();

        $reflectionClass = new ReflectionClass($token);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setValue(objectOrValue: $token, value: $id);
    }

    /**
     * @throws Exception
     */
    private function update(UserToken $token): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update(self::TABLE_NAME)
            ->set('expires_at', ':expires_at')
            ->where('id = :id');

        $this->connection->executeQuery(
            $qb->getSQL(),
            [
                'expires_at' => $token->getExpiresAt()
                    ->setTimezone(timezone: new DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s'),
                'id' => $token->getId(),
            ]
        );
    }
}
