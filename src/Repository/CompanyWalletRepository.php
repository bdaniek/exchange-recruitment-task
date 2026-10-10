<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompanyWallet;
use App\Enum\Currency;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

readonly class CompanyWalletRepository implements CompanyWalletRepositoryInterface
{
    private const string TABLE_NAME = 'company_wallets';

    public function __construct(private Connection $connection)
    {
    }

    /**
     * @throws Exception
     */
    public function findByCurrency(Currency $currency): ?CompanyWallet
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('currency = :currency');

        $row = $this->connection->fetchAssociative($qb->getSQL(), ['currency' => $currency->value]);

        if (!$row) {
            return null;
        }

        return $this->buildEntity($row);
    }

    /**
     * @return CompanyWallet[]
     *
     * @throws Exception
     */
    public function findAll(): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('*')
            ->from(self::TABLE_NAME)
            ->orderBy('currency', 'ASC');

        $rows = $this->connection->fetchAllAssociative($qb->getSQL(), []);

        return array_map($this->buildEntity(...), $rows);
    }

    /**
     * @throws Exception
     */
    public function addToBalance(Currency $currency, string $amount): void
    {
        $now = new DateTimeImmutable()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $existing = $this->findByCurrency($currency);

        $qb = $this->connection->createQueryBuilder();
        if (null === $existing) {
            $qb
                ->insert(self::TABLE_NAME)
                ->values([
                    'currency' => ':currency',
                    'balance' => ':balance',
                    'created_at' => ':created_at',
                    'updated_at' => ':updated_at',
                ]);

            $this->connection->executeStatement($qb->getSQL(), [
                'currency' => $currency->value,
                'balance' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $qb
                ->update(self::TABLE_NAME)
                ->set('balance', 'balance + :amount')
                ->set('updated_at', ':updated_at')
                ->where('currency = :currency');

            $this->connection->executeStatement($qb->getSQL(), [
                'amount' => $amount,
                'updated_at' => $now,
                'currency' => $currency->value,
            ]);
        }
    }

    private function buildEntity(array $row): CompanyWallet
    {
        return new CompanyWallet(
            id: (int) $row['id'],
            currency: Currency::from($row['currency']),
            balance: (float) $row['balance'],
            createdAt: new DateTimeImmutable($row['created_at']),
            updatedAt: new DateTimeImmutable($row['updated_at']),
        );
    }
}
