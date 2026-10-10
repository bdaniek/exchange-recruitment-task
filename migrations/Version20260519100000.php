<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transactions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE `transactions` (
                `id`                        INT           NOT NULL AUTO_INCREMENT,
                `from_wallet_id`            INT           NOT NULL,
                `to_wallet_id`              INT           NOT NULL,
                `from_amount`               DECIMAL(15,4) NOT NULL,
                `to_amount`                 DECIMAL(15,4) NOT NULL,
                `from_currency`             VARCHAR(3)    NOT NULL,
                `to_currency`               VARCHAR(3)    NOT NULL,
                `spread`                    DECIMAL(15,4) NOT NULL,
                `exchange_rate`             DECIMAL(15,6) NOT NULL,
                `status`                    VARCHAR(20)   NOT NULL,
                `requires_anti_fraud_check` TINYINT(1)    NOT NULL DEFAULT 0,
                `anti_fraud_checked_at`     DATETIME      NULL,
                `created_at`                DATETIME      NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `idx_transactions_from_wallet_id` (`from_wallet_id`),
                INDEX `idx_transactions_to_wallet_id`   (`to_wallet_id`),
                INDEX `idx_transactions_status`         (`status`),
                CONSTRAINT `fk_transactions_from_wallet_id`
                    FOREIGN KEY (`from_wallet_id`) REFERENCES `wallets` (`id`)
                    ON DELETE RESTRICT,
                CONSTRAINT `fk_transactions_to_wallet_id`
                    FOREIGN KEY (`to_wallet_id`) REFERENCES `wallets` (`id`)
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `transactions`');
    }
}
