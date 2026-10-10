<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft delete to wallets';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE `wallets`
                ADD `deleted_at` DATETIME NULL,
                ADD `active` TINYINT(1) AS (IF(`deleted_at` IS NULL, 1, NULL)) PERSISTENT
            SQL);
        $this->addSql('ALTER TABLE `wallets` ADD UNIQUE KEY `wallet_user_currency_active_unique` (`user_id`, `currency`, `active`)');
        $this->addSql('ALTER TABLE `wallets` DROP INDEX `wallet_user_currency_unique`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `wallets` ADD UNIQUE KEY `wallet_user_currency_unique` (`user_id`, `currency`)');
        $this->addSql('ALTER TABLE `wallets` DROP INDEX `wallet_user_currency_active_unique`');
        $this->addSql('ALTER TABLE `wallets` DROP COLUMN `active`, DROP COLUMN `deleted_at`');
    }
}
