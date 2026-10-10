<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company_wallets table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE `company_wallets` (
                `id`         INT           NOT NULL AUTO_INCREMENT,
                `currency`   VARCHAR(3)    NOT NULL,
                `balance`    DECIMAL(15,4) NOT NULL DEFAULT 0,
                `created_at` DATETIME      NOT NULL,
                `updated_at` DATETIME      NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `company_wallet_currency_unique` (`currency`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `company_wallets`');
    }
}
