<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wallets table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE `wallets` (
                `id`               INT        NOT NULL AUTO_INCREMENT,
                `user_id`          INT        NOT NULL,
                `currency`         VARCHAR(3) NOT NULL,
                `balance`          DOUBLE     NOT NULL DEFAULT 0,
                `is_blocked`       TINYINT(1) NOT NULL DEFAULT 0,
                `last_activity_at` DATETIME   NULL,
                `created_at`       DATETIME   NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `wallet_user_currency_unique` (`user_id`, `currency`),
                CONSTRAINT `fk_wallets_user_id`
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `wallets`');
    }
}
